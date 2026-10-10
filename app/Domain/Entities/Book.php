<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\BookNotAvailableException;
use App\Domain\Exceptions\DomainValidationException;
use App\Domain\ValueObjects\Isbn;
use DateTimeImmutable;

final class Book
{
    private string $id;
    private string $title;
    private string $author;
    private Isbn $isbn;
    private string $categoryId;
    private int $totalCopies;
    private int $availableCopies;
    private ?string $coverKey;
    private ?string $categoryName;
    private ?DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies,
        int $availableCopies,
        ?string $coverKey = null,
        ?string $categoryName = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
        ?DateTimeImmutable $deletedAt = null
    ) {
        $this->id = $id;
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->isbn = $isbn;
        $this->setCategoryId($categoryId);

        if ($totalCopies < 1) {
            throw new DomainValidationException("El total de ejemplares debe ser mayor o igual a 1.");
        }
        $this->totalCopies = $totalCopies;

        if ($availableCopies < 0 || $availableCopies > $totalCopies) {
            throw new DomainValidationException("Los ejemplares disponibles deben estar entre 0 y el total de ejemplares.");
        }
        $this->availableCopies = $availableCopies;

        $this->coverKey = ($coverKey !== null && trim($coverKey) !== '') ? trim($coverKey) : null;
        $this->categoryName = $categoryName;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

    public static function create(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies
    ): self {
        return new self($id, $title, $author, $isbn, $categoryId, $totalCopies, $totalCopies);
    }

    // Spec concise methods
    public function id(): string { return $this->id; }
    public function title(): string { return $this->title; }
    public function author(): string { return $this->author; }
    public function isbn(): Isbn { return $this->isbn; }
    public function categoryId(): string { return $this->categoryId; }
    public function totalCopies(): int { return $this->totalCopies; }
    public function availableCopies(): int { return $this->availableCopies; }
    public function coverKey(): ?string { return $this->coverKey; }
    public function categoryName(): ?string { return $this->categoryName; }
    public function deletedAt(): ?DateTimeImmutable { return $this->deletedAt; }
    public function isActive(): bool { return $this->deletedAt === null; }

    // JavaBean getters & aliases for complete interop
    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getIsbn(): Isbn { return $this->isbn; }
    public function getCategoryId(): string { return $this->categoryId; }
    public function getTotalCopies(): int { return $this->totalCopies; }
    public function getAvailableCopies(): int { return $this->availableCopies; }
    public function getCoverKey(): ?string { return $this->coverKey; }
    public function getCoverImage(): ?string { return $this->coverKey; }
    public function getCategoryName(): ?string { return $this->categoryName; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTimeImmutable { return $this->deletedAt; }
    public function isDeleted(): bool { return $this->deletedAt !== null; }

    public function setTitle(string $title): void
    {
        $clean = trim($title);
        if ($clean === '') {
            throw new DomainValidationException("El título del libro es obligatorio.");
        }
        $this->title = $clean;
    }

    public function setAuthor(string $author): void
    {
        $clean = trim($author);
        if ($clean === '') {
            throw new DomainValidationException("El autor del libro es obligatorio.");
        }
        $this->author = $clean;
    }

    public function setCategoryId(string $categoryId): void
    {
        $clean = trim($categoryId);
        if ($clean === '') {
            throw new DomainValidationException("La categoría es obligatoria.");
        }
        $this->categoryId = $clean;
    }

    public function updateTotalCopies(int $newTotal): void
    {
        if ($newTotal < 1) {
            throw new DomainValidationException("El total de ejemplares debe ser al menos 1.");
        }

        $diff = $newTotal - $this->totalCopies;
        $newAvailable = $this->availableCopies + $diff;

        if ($newAvailable < 0) {
            throw new DomainValidationException("No se puede reducir el total a {$newTotal} porque dejaría ejemplares disponibles negativos.");
        }

        $this->totalCopies = $newTotal;
        $this->availableCopies = $newAvailable;
    }

    public function update(string $title, string $author, string $categoryId, int $totalCopies): void
    {
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->setCategoryId($categoryId);
        $this->updateTotalCopies($totalCopies);
    }

    public function withdrawCopy(): void
    {
        if ($this->availableCopies <= 0) {
            throw new BookNotAvailableException("No quedan ejemplares disponibles de «{$this->title}».");
        }
        $this->availableCopies--;
    }

    public function withdraw(): void
    {
        $this->withdrawCopy();
    }

    public function returnCopy(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new DomainValidationException("Los ejemplares disponibles no pueden superar el total.");
        }
        $this->availableCopies++;
    }

    public function restock(): void
    {
        $this->returnCopy();
    }

    public function attachCover(?string $key): void
    {
        $this->coverKey = ($key !== null && trim($key) !== '') ? trim($key) : null;
    }

    public function softDelete(?DateTimeImmutable $now = null): void
    {
        $this->deletedAt = $now ?? new DateTimeImmutable();
    }
}
