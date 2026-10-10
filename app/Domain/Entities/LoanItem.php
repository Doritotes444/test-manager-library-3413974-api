<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\ItemAlreadyReturnedException;
use DateTimeImmutable;

final class LoanItem
{
    private string $id;
    private string $loanId;
    private string $bookId;
    private string $bookTitle;
    private string $isbn;
    private string $categoryName;
    private ?DateTimeImmutable $dueAt;
    private ?DateTimeImmutable $returnedAt;

    public function __construct(
        string $id,
        string $loanId,
        string $bookId,
        string $bookTitle,
        string $isbn,
        string $categoryName,
        ?DateTimeImmutable $dueAt = null,
        ?DateTimeImmutable $returnedAt = null
    ) {
        $this->id = $id;
        $this->loanId = $loanId;
        $this->bookId = $bookId;
        $this->bookTitle = $bookTitle;
        $this->isbn = $isbn;
        $this->categoryName = $categoryName;
        $this->dueAt = $dueAt;
        $this->returnedAt = $returnedAt;
    }

    // Concise methods
    public function id(): string { return $this->id; }
    public function loanId(): string { return $this->loanId; }
    public function bookId(): string { return $this->bookId; }
    public function bookTitle(): string { return $this->bookTitle; }
    public function isbn(): string { return $this->isbn; }
    public function categoryName(): string { return $this->categoryName; }
    public function dueAt(): ?DateTimeImmutable { return $this->dueAt; }
    public function returnedAt(): ?DateTimeImmutable { return $this->returnedAt; }

    // JavaBean getters & aliases
    public function getId(): string { return $this->id; }
    public function getLoanId(): string { return $this->loanId; }
    public function getBookId(): string { return $this->bookId; }
    public function getBookTitle(): string { return $this->bookTitle; }
    public function getIsbn(): string { return $this->isbn; }
    public function getCategoryName(): string { return $this->categoryName; }
    public function getDueDate(): ?DateTimeImmutable { return $this->dueAt; }
    public function getReturnedAt(): ?DateTimeImmutable { return $this->returnedAt; }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdue(?DateTimeImmutable $now = null): bool
    {
        $current = $now ?? new DateTimeImmutable();
        return !$this->isReturned() && $this->dueAt !== null && $this->dueAt < $current;
    }

    public function returnItem(?DateTimeImmutable $now = null): void
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturnedException("La línea de préstamo ya fue devuelta previamente.");
        }
        $this->returnedAt = $now ?? new DateTimeImmutable();
    }

    public function markReturned(?DateTimeImmutable $now = null): void
    {
        $this->returnItem($now);
    }
}
