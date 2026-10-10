<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\DomainValidationException;
use App\Domain\Exceptions\ResourceNotFoundException;
use DateTimeImmutable;

final class Loan
{
    private string $id;
    private string $memberId;
    private string $loanedByUserId;
    private DateTimeImmutable $loanedAt;
    private DateTimeImmutable $dueDate;
    /** @var array<string, LoanItem> indexed by bookId */
    private array $items = [];

    public function __construct(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        ?DateTimeImmutable $dueDate = null,
        array $items = []
    ) {
        $this->id = $id;
        $this->memberId = $memberId;
        $this->loanedByUserId = $loanedByUserId;
        $this->loanedAt = $loanedAt;
        $this->dueDate = $dueDate ?? $loanedAt->modify('+14 days');

        foreach ($items as $item) {
            if ($item instanceof LoanItem) {
                $this->items[$item->bookId()] = $item;
            }
        }
    }

    public static function open(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt
    ): self {
        return new self($id, $memberId, $loanedByUserId, $loanedAt);
    }

    // Concise methods
    public function id(): string { return $this->id; }
    public function memberId(): string { return $this->memberId; }
    public function loanedByUserId(): string { return $this->loanedByUserId; }
    public function loanedAt(): DateTimeImmutable { return $this->loanedAt; }
    public function dueDate(): DateTimeImmutable { return $this->dueDate; }
    /** @return list<LoanItem> */
    public function items(): array { return array_values($this->items); }

    // JavaBean getters & aliases
    public function getId(): string { return $this->id; }
    public function getMemberId(): string { return $this->memberId; }
    public function getUserId(): string { return $this->loanedByUserId; }
    public function getLoanedByUserId(): string { return $this->loanedByUserId; }
    public function getLoanedAt(): DateTimeImmutable { return $this->loanedAt; }
    public function getDueDate(): DateTimeImmutable { return $this->dueDate; }
    /** @return list<LoanItem> */
    public function getItems(): array { return array_values($this->items); }

    public function getItemById(string $itemId): ?LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id() === $itemId) {
                return $item;
            }
        }
        return null;
    }

    public function addItem(
        string $itemId,
        string|Book $bookOrId,
        ?string $bookTitle = null,
        ?string $isbn = null,
        ?string $categoryName = null,
        ?DateTimeImmutable $returnedAt = null
    ): LoanItem {
        $bookId = ($bookOrId instanceof Book) ? $bookOrId->id() : $bookOrId;
        $title = ($bookOrId instanceof Book) ? $bookOrId->title() : ($bookTitle ?? 'Libro');
        $isbnStr = ($bookOrId instanceof Book) ? $bookOrId->isbn()->value() : ($isbn ?? '0000000000000');
        $cat = ($bookOrId instanceof Book) ? ($bookOrId->categoryName() ?? 'General') : ($categoryName ?? 'General');

        if (isset($this->items[$bookId])) {
            throw new DuplicateBookInLoanException("No se puede incluir el mismo libro más de una vez en el préstamo.");
        }

        if (count($this->items) >= 3) {
            throw new DomainValidationException("Un préstamo solo puede incluir de 1 a 3 libros.");
        }

        if ($bookOrId instanceof Book) {
            $bookOrId->withdrawCopy();
        }

        $dueAt = $this->dueDate;
        $item = new LoanItem(
            $itemId,
            $this->id,
            $bookId,
            $title,
            $isbnStr,
            $cat,
            $dueAt,
            $returnedAt
        );

        $this->items[$bookId] = $item;
        return $item;
    }

    public function returnItem(string $loanItemId, ?DateTimeImmutable $now = null): void
    {
        $item = $this->getItemById($loanItemId);
        if (!$item) {
            throw new ResourceNotFoundException("Línea de préstamo no encontrada en este préstamo.");
        }
        $item->returnItem($now);
    }

    public function status(?DateTimeImmutable $now = null): string
    {
        $current = $now ?? new DateTimeImmutable();
        $allReturned = true;
        $anyOverdue = false;

        foreach ($this->items as $item) {
            if (!$item->isReturned()) {
                $allReturned = false;
                if ($item->isOverdue($current)) {
                    $anyOverdue = true;
                }
            }
        }

        if ($allReturned && count($this->items) > 0) {
            return 'closed';
        }

        if ($anyOverdue) {
            return 'overdue';
        }

        return 'open';
    }

    public function getStatus(?DateTimeImmutable $now = null): string
    {
        return $this->status($now);
    }
}
