<?php

namespace App\Application\Services;

use App\Application\DTOs\BookDTO;
use App\Application\DTOs\CreateBookCommand;
use App\Application\DTOs\UpdateBookCommand;
use App\Application\DTOs\BookQuery;
use App\Application\DTOs\MemberDTO;
use App\Application\DTOs\CreateMemberCommand;
use App\Application\DTOs\UpdateMemberCommand;
use App\Application\DTOs\LoanDTO;
use App\Application\DTOs\LendBooksCommand;
use App\Application\DTOs\UserDTO;
use App\Application\Ports\Outbound\BookRepositoryInterface;
use App\Application\Ports\Outbound\LoanRepositoryInterface;
use App\Application\Ports\Outbound\MemberRepositoryInterface;
use App\Application\Ports\Outbound\CategoryRepositoryInterface;
use App\Application\Ports\Outbound\UserRepositoryInterface;
use App\Application\Ports\Outbound\ClockInterface;
use App\Application\Ports\Outbound\PasswordHasherInterface;
use App\Application\Ports\Outbound\TokenGeneratorInterface;
use App\Application\Ports\Outbound\FileStorageInterface;
use App\Application\Ports\Outbound\MostBorrowedReportQueryInterface;
use App\Domain\Entities\Book;
use App\Domain\Entities\Loan;
use App\Domain\Entities\Member;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Isbn;
use App\Domain\Exceptions\ResourceNotFoundException;
use App\Domain\Exceptions\IsbnTakenException;
use App\Domain\Exceptions\DocumentTakenException;
use App\Domain\Exceptions\BookHasOpenLoansException;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\UsernameTakenException;
use App\Domain\Exceptions\MemberHasOverdueLoansException;
use App\Domain\Exceptions\MemberLimitExceededException;
use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\BookNotAvailableException;
use App\Domain\Exceptions\ItemAlreadyReturnedException;
use App\Application\Ports\Outbound\UnitOfWorkInterface;
use Ramsey\Uuid\Uuid;

class LendBooksService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepo,
        private MemberRepositoryInterface $memberRepo,
        private BookRepositoryInterface $bookRepo,
        private ClockInterface $clock,
        private ?UnitOfWorkInterface $uow = null
    ) {}

    public function lend(LendBooksCommand $cmd): LoanDTO
    {
        $member = $this->memberRepo->findById($cmd->memberId);
        if (!$member) {
            throw new ResourceNotFoundException("Socio no encontrado");
        }

        if ($this->loanRepo->hasOverdueLoans($member->id())) {
            throw new MemberHasOverdueLoansException();
        }

        $activeCount = $this->loanRepo->countActiveItemsByMember($member->id());
        $requestedCount = count($cmd->bookIds);
        if ($activeCount + $requestedCount > 3) {
            throw new MemberLimitExceededException("El socio superaría el límite de 3 libros activos");
        }

        if (count($cmd->bookIds) !== count(array_unique($cmd->bookIds))) {
            throw new DuplicateBookInLoanException();
        }

        $now = $this->clock->now();
        $loan = new Loan(
            Uuid::uuid4()->toString(),
            $member->id(),
            $cmd->userId,
            $now
        );

        $runInTx = function() use ($cmd, $loan) {
            foreach ($cmd->bookIds as $bookId) {
                $book = $this->bookRepo->findById($bookId);
                if (!$book) {
                    throw new ResourceNotFoundException("Libro no encontrado: {$bookId}");
                }
                if ($book->availableCopies() < 1) {
                    throw new BookNotAvailableException("El libro '{$book->title()}' no tiene copias disponibles");
                }

                $book->withdrawCopy();
                $this->bookRepo->save($book);

                $loan->addItem(
                    Uuid::uuid4()->toString(),
                    $book->id(),
                    $book->title(),
                    $book->isbn()->value(),
                    $book->categoryName() ?? 'General'
                );
            }

            $this->loanRepo->save($loan);
        };

        if ($this->uow !== null) {
            $this->uow->transaction($runInTx);
        } else {
            $runInTx();
        }

        return LoanDTO::fromEntity($loan, $member->fullName(), $member->documentNumber());
    }
}

class ReturnItemService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepo,
        private BookRepositoryInterface $bookRepo,
        private ClockInterface $clock,
        private ?UnitOfWorkInterface $uow = null
    ) {}

    public function returnItem(string $loanId, string $loanItemId): array
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            throw new ResourceNotFoundException("Préstamo no encontrado");
        }

        $item = $loan->getItemById($loanItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Línea de préstamo no encontrada");
        }

        if ($item->isReturned()) {
            throw new ItemAlreadyReturnedException();
        }

        $now = $this->clock->now();
        $runInTx = function() use ($loan, $loanItemId, $item, $now) {
            $loan->returnItem($loanItemId, $now);
            $this->loanRepo->save($loan);

            $book = $this->bookRepo->findById($item->bookId());
            if ($book) {
                $book->returnCopy();
                $this->bookRepo->save($book);
            }
        };

        if ($this->uow !== null) {
            $this->uow->transaction($runInTx);
        } else {
            $runInTx();
        }

        return [
            'id' => $item->id(),
            'book_id' => $item->bookId(),
            'returned_at' => $now->format('Y-m-d\TH:i:s\Z'),
            'loan' => LoanDTO::fromEntity($loan)
        ];
    }
}

class CreateBookService
{
    public function __construct(
        private BookRepositoryInterface $bookRepo,
        private CategoryRepositoryInterface $catRepo,
        private ?FileStorageInterface $fileStorage = null
    ) {}

    public function create(CreateBookCommand $cmd): BookDTO
    {
        if ($this->bookRepo->isbnExists($cmd->isbn)) {
            throw new IsbnTakenException();
        }

        $cat = $this->catRepo->findById($cmd->categoryId);
        if (!$cat) {
            throw new ResourceNotFoundException("Categoría no encontrada");
        }

        $coverUrl = null;
        if ($cmd->coverFile && $this->fileStorage) {
            $coverUrl = $this->fileStorage->store($cmd->coverFile, 'covers');
        }

        $book = new Book(
            Uuid::uuid4()->toString(),
            $cmd->title,
            $cmd->author,
            new Isbn($cmd->isbn),
            $cmd->categoryId,
            $cmd->totalCopies,
            $cmd->totalCopies,
            $coverUrl,
            $cat->name()
        );

        $this->bookRepo->save($book);

        return BookDTO::fromEntity($book);
    }
}

class SearchBooksService
{
    public function __construct(private BookRepositoryInterface $bookRepo) {}

    public function search(BookQuery $query): array
    {
        $books = $this->bookRepo->search($query);
        return array_map(fn($b) => BookDTO::fromEntity($b), $books);
    }

    public function findById(string $id): BookDTO
    {
        $book = $this->bookRepo->findById($id);
        if (!$book) {
            throw new ResourceNotFoundException("Libro no encontrado");
        }
        return BookDTO::fromEntity($book);
    }

    public function update(string $id, UpdateBookCommand $cmd): BookDTO
    {
        $book = $this->bookRepo->findById($id);
        if (!$book) {
            throw new ResourceNotFoundException("Libro no encontrado");
        }

        $book->update(
            $cmd->title,
            $cmd->author,
            $cmd->categoryId,
            $cmd->totalCopies
        );

        $this->bookRepo->save($book);

        return BookDTO::fromEntity($book);
    }

    public function delete(string $id): void
    {
        $book = $this->bookRepo->findById($id);
        if (!$book) {
            throw new ResourceNotFoundException("Libro no encontrado");
        }

        if ($this->bookRepo->hasOpenLoans($id)) {
            throw new BookHasOpenLoansException();
        }

        $this->bookRepo->delete($id);
    }
}

class OverdueService
{
    public function __construct(
        private LoanRepositoryInterface $loanRepo,
        private ?ClockInterface $clock = null
    ) {}

    public function getOverdueItems(): array
    {
        $now = $this->clock ? $this->clock->now() : new \DateTimeImmutable();
        return $this->loanRepo->getOverdueItems($now);
    }
}

class MostBorrowedReportService
{
    public function __construct(private MostBorrowedReportQueryInterface $query) {}

    public function getMostBorrowed(int $limit = 10, ?string $from = null, ?string $to = null): array
    {
        return $this->query->execute($limit, $from, $to);
    }
}

class LoginService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private PasswordHasherInterface $hasher,
        private TokenGeneratorInterface $tokenGen
    ) {}

    public function login(string $username, string $password): array
    {
        $user = $this->userRepo->findByUsername($username);
        if (!$user) {
            throw new InvalidCredentialsException();
        }

        if (!$this->hasher->verify($password, $user->passwordHash())) {
            throw new InvalidCredentialsException();
        }

        $token = $this->tokenGen->generate([
            'sub' => $user->id(),
            'role' => $user->role(),
        ]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 28800,
            'role' => $user->role(),
            'token' => $token, // UI backwards compatibility
            'user' => UserDTO::fromEntity($user)
        ];
    }
}

class CreateLibrarianService
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private PasswordHasherInterface $hasher
    ) {}

    public function create(string $username, string $password, string $role = 'librarian'): UserDTO
    {
        if ($this->userRepo->usernameExists($username)) {
            throw new UsernameTakenException();
        }

        $hash = $this->hasher->hash($password);
        $user = new User(
            Uuid::uuid4()->toString(),
            $username,
            $username,
            $hash,
            $role
        );

        $this->userRepo->save($user);

        return UserDTO::fromEntity($user);
    }
}

class MemberService
{
    public function __construct(
        private MemberRepositoryInterface $memberRepo,
        private LoanRepositoryInterface $loanRepo
    ) {}

    public function list(array $filters = []): array
    {
        $members = $this->memberRepo->findAll($filters);
        return array_map(function($m) {
            $active = $this->loanRepo->countActiveItemsByMember($m->id());
            $overdue = $this->loanRepo->hasOverdueLoans($m->id());
            return MemberDTO::fromEntity($m, $active, $overdue);
        }, $members);
    }

    public function findById(string $id): MemberDTO
    {
        $member = $this->memberRepo->findById($id);
        if (!$member) {
            throw new ResourceNotFoundException("Socio no encontrado");
        }
        $active = $this->loanRepo->countActiveItemsByMember($member->id());
        $overdue = $this->loanRepo->hasOverdueLoans($member->id());
        return MemberDTO::fromEntity($member, $active, $overdue);
    }

    public function create(CreateMemberCommand $cmd): MemberDTO
    {
        if ($this->memberRepo->documentExists($cmd->documentType, $cmd->documentNumber)) {
            throw new DocumentTakenException();
        }

        $member = new Member(
            Uuid::uuid4()->toString(),
            $cmd->fullName,
            $cmd->documentType,
            $cmd->documentNumber,
            $cmd->email,
            $cmd->phone
        );

        $this->memberRepo->save($member);

        return MemberDTO::fromEntity($member, 0, false);
    }

    public function update(string $id, UpdateMemberCommand $cmd): MemberDTO
    {
        $member = $this->memberRepo->findById($id);
        if (!$member) {
            throw new ResourceNotFoundException("Socio no encontrado");
        }

        $member->updateContact($cmd->fullName, $cmd->email, $cmd->phone);
        $this->memberRepo->save($member);

        $active = $this->loanRepo->countActiveItemsByMember($member->id());
        $overdue = $this->loanRepo->hasOverdueLoans($member->id());
        return MemberDTO::fromEntity($member, $active, $overdue);
    }
}

class CategoryService
{
    public function __construct(private CategoryRepositoryInterface $catRepo) {}

    public function getCategories(): array
    {
        $cats = $this->catRepo->findAll();
        return array_map(fn($c) => [
            'id' => $c->id(),
            'name' => $c->name()
        ], $cats);
    }
}
