<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Book;
use App\Domain\Entities\Category;
use App\Domain\Entities\Loan;
use App\Domain\Entities\LoanItem;
use App\Domain\Entities\Member;
use App\Domain\Entities\User;

// --- Auth DTOs ---
final class LoginDTO {
    public function __construct(public string $username, public string $password) {}
}

final class AuthResultDTO {
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public string $role
    ) {}
}

final class UserDTO {
    public function __construct(
        public string $id,
        public string $username,
        public string $role,
        public ?string $name = null
    ) {}

    public static function fromEntity(User $user): self {
        return new self(
            $user->id(),
            $user->username(),
            $user->role(),
            $user->name()
        );
    }
}

// --- Book DTOs & Commands ---
final class BookDTO {
    public function __construct(
        public string $id,
        public string $title,
        public string $author,
        public string $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey = null,
        public ?string $categoryName = null,
        public ?array $category = null
    ) {}

    public static function fromEntity(Book $book): self {
        return new self(
            $book->id(),
            $book->title(),
            $book->author(),
            $book->isbn()->value(),
            $book->categoryId(),
            $book->totalCopies(),
            $book->availableCopies(),
            $book->coverKey(),
            $book->categoryName(),
            ['id' => $book->categoryId(), 'name' => $book->categoryName() ?? 'General']
        );
    }
}

final class CreateBookCommand {
    public function __construct(
        public string $title,
        public string $author,
        public string $isbn,
        public string $categoryId,
        public int $totalCopies,
        public mixed $coverFile = null
    ) {}
}

final class UpdateBookCommand {
    public function __construct(
        public string $title,
        public string $author,
        public string $categoryId,
        public int $totalCopies,
        public mixed $coverFile = null
    ) {}
}

final class BookQuery {
    public function __construct(
        public ?string $search = null,
        public ?string $categoryId = null,
        public bool $availableOnly = false,
        public int $limit = 20,
        public int $offset = 0,
        public int $page = 1
    ) {}
}

// --- Member DTOs & Commands ---
final class MemberDTO {
    public function __construct(
        public string $id,
        public string $fullName,
        public string $documentNumber,
        public int $activeLoans = 0,
        public bool $hasOverdue = false,
        // UI compatibility aliases
        public ?string $name = null,
        public ?string $documentType = 'CC',
        public ?string $email = null,
        public ?string $phone = null
    ) {
        $this->name = $fullName;
    }

    public static function fromEntity(Member $m, int $activeLoans = 0, bool $hasOverdue = false): self {
        return new self(
            $m->id(),
            $m->fullName(),
            $m->documentNumber(),
            $activeLoans,
            $hasOverdue,
            $m->fullName(),
            $m->documentType(),
            $m->email(),
            $m->phone()
        );
    }
}

final class CreateMemberCommand {
    public function __construct(
        public string $fullName,
        public string $documentNumber,
        public string $documentType = 'CC',
        public string $email = '',
        public string $phone = ''
    ) {}
}

final class UpdateMemberCommand {
    public function __construct(
        public string $fullName,
        public string $email = '',
        public string $phone = ''
    ) {}
}

// --- Loan DTOs & Commands ---
final class LoanItemDTO {
    public function __construct(
        public string $id,
        public string $bookId,
        public string $bookTitle,
        public string $isbn,
        public string $categoryName,
        public string $dueAt,
        public ?string $returnedAt = null
    ) {}

    public static function fromEntity(LoanItem $it): self {
        return new self(
            $it->id(),
            $it->bookId(),
            $it->bookTitle(),
            $it->isbn(),
            $it->categoryName(),
            $it->dueAt() ? $it->dueAt()->format('Y-m-d\TH:i:s\Z') : '',
            $it->returnedAt() ? $it->returnedAt()->format('Y-m-d\TH:i:s\Z') : null
        );
    }
}

final class LoanDTO {
    /** @param list<LoanItemDTO> $items */
    public function __construct(
        public string $id,
        public string $memberId,
        public string $loanedAt,
        public string $status,
        public array $items = [],
        public ?string $dueDate = null,
        public ?string $memberName = null,
        public ?string $memberDocument = null
    ) {}

    public static function fromEntity(Loan $loan, ?string $memberName = null, ?string $memberDoc = null): self {
        $items = array_map(fn($it) => LoanItemDTO::fromEntity($it), $loan->items());
        return new self(
            $loan->id(),
            $loan->memberId(),
            $loan->loanedAt()->format('Y-m-d\TH:i:s\Z'),
            $loan->status(),
            $items,
            $loan->dueDate()->format('Y-m-d\TH:i:s\Z'),
            $memberName,
            $memberDoc
        );
    }
}

final class LendBooksCommand {
    /** @param list<string> $bookIds */
    public function __construct(
        public string $memberId,
        public array $bookIds,
        public string $userId
    ) {}
}
