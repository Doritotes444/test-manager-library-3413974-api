<?php

namespace App\Adapters\Outbound\Persistence\Repositories;

use App\Application\DTOs\BookQuery;
use App\Application\Ports\Outbound\BookRepositoryInterface;
use App\Application\Ports\Outbound\CategoryRepositoryInterface;
use App\Application\Ports\Outbound\LoanRepositoryInterface;
use App\Application\Ports\Outbound\MemberRepositoryInterface;
use App\Application\Ports\Outbound\UserRepositoryInterface;
use App\Application\Ports\Outbound\MostBorrowedReportQueryInterface;
use App\Domain\Entities\Book;
use App\Domain\Entities\Category;
use App\Domain\Entities\Loan;
use App\Domain\Entities\Member;
use App\Domain\Entities\User;
use App\Adapters\Outbound\Persistence\Models\BookModel;
use App\Adapters\Outbound\Persistence\Models\CategoryModel;
use App\Adapters\Outbound\Persistence\Models\LoanModel;
use App\Adapters\Outbound\Persistence\Models\LoanItemModel;
use App\Adapters\Outbound\Persistence\Models\MemberModel;
use App\Adapters\Outbound\Persistence\Models\UserModel;
use App\Adapters\Outbound\Persistence\Mappers\BookMapper;
use App\Adapters\Outbound\Persistence\Mappers\CategoryMapper;
use App\Adapters\Outbound\Persistence\Mappers\LoanMapper;
use App\Adapters\Outbound\Persistence\Mappers\MemberMapper;
use App\Adapters\Outbound\Persistence\Mappers\UserMapper;
use Illuminate\Support\Facades\DB;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function findAll(): array
    {
        return CategoryModel::orderBy('name')->get()->map(fn($m) => CategoryMapper::toDomain($m))->all();
    }

    public function findById(string $id): ?Category
    {
        $m = CategoryModel::find($id);
        return $m ? CategoryMapper::toDomain($m) : null;
    }
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $m = UserModel::find($id);
        return $m ? UserMapper::toDomain($m) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $m = UserModel::where('username', mb_strtolower(trim($username), 'UTF-8'))->first();
        return $m ? UserMapper::toDomain($m) : null;
    }

    public function usernameExists(string $username): bool
    {
        return UserModel::where('username', mb_strtolower(trim($username), 'UTF-8'))->exists();
    }

    public function save(User $user): void
    {
        UserModel::updateOrCreate(
            ['id' => $user->id()],
            [
                'username' => $user->username(),
                'password_hash' => $user->passwordHash(),
                'role' => $user->role(),
            ]
        );
    }
}

class EloquentMemberRepository implements MemberRepositoryInterface
{
    public function findById(string $id): ?Member
    {
        $m = MemberModel::find($id);
        return $m ? MemberMapper::toDomain($m) : null;
    }

    public function findByDocument(string $documentType, string $documentNumber): ?Member
    {
        $m = MemberModel::where('document_number', trim($documentNumber))->first();
        return $m ? MemberMapper::toDomain($m) : null;
    }

    public function documentExists(string $documentType, string $documentNumber): bool
    {
        return MemberModel::where('document_number', trim($documentNumber))->exists();
    }

    public function findAll(array $filters = []): array
    {
        $q = MemberModel::query();
        if (!empty($filters['q']) || !empty($filters['search'])) {
            $s = '%' . trim($filters['q'] ?? $filters['search']) . '%';
            $q->where(fn($sub) => $sub->where('full_name', 'like', $s)
                ->orWhere('document_number', 'like', $s));
        }
        return $q->orderBy('full_name')->get()->map(fn($m) => MemberMapper::toDomain($m))->all();
    }

    public function save(Member $member): void
    {
        MemberModel::updateOrCreate(
            ['id' => $member->id()],
            [
                'full_name' => $member->fullName(),
                'document_number' => $member->documentNumber(),
            ]
        );
    }
}

class EloquentBookRepository implements BookRepositoryInterface
{
    public function findById(string $id): ?Book
    {
        $m = BookModel::with('category')->find($id);
        return $m ? BookMapper::toDomain($m) : null;
    }

    public function findByIsbn(string $isbn): ?Book
    {
        $m = BookModel::with('category')->where('isbn', trim($isbn))->first();
        return $m ? BookMapper::toDomain($m) : null;
    }

    public function isbnExists(string $isbn): bool
    {
        return BookModel::where('isbn', trim($isbn))->exists();
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return LoanItemModel::where('book_id', $bookId)
            ->whereNull('returned_at')
            ->exists();
    }

    public function search(BookQuery $query): array
    {
        $q = BookModel::with('category');
        if ($query->search) {
            $s = '%' . trim($query->search) . '%';
            $q->where(fn($sub) => $sub->where('title', 'like', $s)
                ->orWhere('author', 'like', $s)
                ->orWhere('isbn', 'like', $s));
        }
        if ($query->categoryId) {
            $q->where('category_id', $query->categoryId);
        }
        if ($query->availableOnly) {
            $q->where('available_copies', '>', 0);
        }
        return $q->orderBy('title')
            ->limit($query->limit)
            ->offset($query->offset)
            ->get()
            ->map(fn($m) => BookMapper::toDomain($m))
            ->all();
    }

    public function save(Book $book): void
    {
        BookModel::updateOrCreate(
            ['id' => $book->id()],
            [
                'title' => $book->title(),
                'author' => $book->author(),
                'isbn' => $book->isbn()->value(),
                'category_id' => $book->categoryId(),
                'total_copies' => $book->totalCopies(),
                'available_copies' => $book->availableCopies(),
                'cover_key' => $book->coverKey(),
                'deleted_at' => $book->deletedAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function delete(string $id): void
    {
        BookModel::destroy($id);
    }
}

class EloquentLoanRepository implements LoanRepositoryInterface
{
    public function findById(string $id): ?Loan
    {
        $m = LoanModel::with(['items', 'member'])->find($id);
        return $m ? LoanMapper::toDomain($m) : null;
    }

    public function findAll(array $filters = []): array
    {
        $q = LoanModel::with(['items', 'member']);
        if (!empty($filters['member_id'])) {
            $q->where('member_id', $filters['member_id']);
        }
        if (!empty($filters['from'])) {
            $q->where('loaned_at', '>=', $filters['from'] . ' 00:00:00');
        }
        if (!empty($filters['to'])) {
            $q->where('loaned_at', '<=', $filters['to'] . ' 23:59:59');
        }

        $all = $q->orderBy('loaned_at', 'desc')->get()->map(fn($m) => LoanMapper::toDomain($m))->all();

        if (!empty($filters['status'])) {
            $status = $filters['status'];
            return array_values(array_filter($all, fn(Loan $l) => $l->status() === $status));
        }

        return $all;
    }

    public function countActiveItemsByMember(string $memberId): int
    {
        return LoanItemModel::whereHas('loan', fn($q) => $q->where('member_id', $memberId))
            ->whereNull('returned_at')
            ->count();
    }

    public function hasOverdueLoans(string $memberId): bool
    {
        $now = now()->toDateTimeString();
        // Checked against due_at in loan_item table!
        return LoanItemModel::whereHas('loan', fn($q) => $q->where('member_id', $memberId))
            ->where('due_at', '<', $now)
            ->whereNull('returned_at')
            ->exists();
    }

    public function save(Loan $loan): void
    {
        DB::transaction(function() use ($loan) {
            LoanModel::updateOrCreate(
                ['id' => $loan->id()],
                [
                    'member_id' => $loan->memberId(),
                    'loaned_by_user_id' => $loan->loanedByUserId(),
                    'loaned_at' => $loan->loanedAt()->format('Y-m-d H:i:s'),
                ]
            );

            foreach ($loan->items() as $item) {
                LoanItemModel::updateOrCreate(
                    ['id' => $item->id()],
                    [
                        'loan_id' => $loan->id(),
                        'book_id' => $item->bookId(),
                        'book_title' => $item->bookTitle(),
                        'isbn' => $item->isbn(),
                        'category_name' => $item->categoryName(),
                        'due_at' => $item->dueAt()->format('Y-m-d H:i:s'),
                        'returned_at' => $item->returnedAt()?->format('Y-m-d H:i:s'),
                    ]
                );
            }
        });
    }

    public function saveItem(LoanItem $item): void
    {
        LoanItemModel::updateOrCreate(
            ['id' => $item->id()],
            [
                'loan_id' => $item->loanId(),
                'book_id' => $item->bookId(),
                'book_title' => $item->bookTitle(),
                'isbn' => $item->isbn(),
                'category_name' => $item->categoryName(),
                'due_at' => $item->dueAt()->format('Y-m-d H:i:s'),
                'returned_at' => $item->returnedAt()?->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function searchPaginated(
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $memberId,
        int $page,
        int $size
    ): array {
        $filters = [];
        if ($from) $filters['from'] = $from->format('Y-m-d');
        if ($to) $filters['to'] = $to->format('Y-m-d');
        if ($memberId) $filters['member_id'] = $memberId;

        $all = $this->findAll($filters);
        $total = count($all);
        $sliced = array_slice($all, max(0, ($page - 1) * $size), $size);

        return [
            'items' => $sliced,
            'total' => $total
        ];
    }

    public function getOverdueItems(\DateTimeImmutable $now): array
    {
        $nowStr = $now->format('Y-m-d H:i:s');
        $items = LoanItemModel::with(['loan.member'])
            ->where('due_at', '<', $nowStr)
            ->whereNull('returned_at')
            ->get();

        return $items->map(function ($it) {
            $due = new \DateTimeImmutable($it->due_at);
            $diff = (new \DateTimeImmutable())->diff($due)->days;
            return [
                'loan_id' => $it->loan_id,
                'item_id' => $it->id,
                'book_title' => $it->book_title,
                'member_name' => $it->loan?->member?->full_name ?? 'Socio',
                'due_at' => $due->format('Y-m-d\TH:i:s\Z'),
                'days_late' => $diff
            ];
        })->all();
    }
}

class DatabaseMostBorrowedReportQuery implements MostBorrowedReportQueryInterface
{
    public function execute(int $limit = 10, ?string $from = null, ?string $to = null): array
    {
        $q = DB::table('loan_item')
            ->select([
                'loan_item.book_id as book_id',
                'loan_item.book_title as book_title',
                'loan_item.category_name as category_name',
                DB::raw('COUNT(*) as times_borrowed')
            ])
            ->join('loan', 'loan.id', '=', 'loan_item.loan_id');

        if ($from) {
            $q->where('loan.loaned_at', '>=', $from . ' 00:00:00');
        }
        if ($to) {
            $q->where('loan.loaned_at', '<=', $to . ' 23:59:59');
        }

        return $q->groupBy('loan_item.book_id', 'loan_item.book_title', 'loan_item.category_name')
            ->orderByDesc('times_borrowed')
            ->orderBy('loan_item.book_title')
            ->limit($limit)
            ->get()
            ->map(fn($row) => (array)$row)
            ->all();
    }
}
