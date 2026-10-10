<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
use App\Domain\Entities\Book;
use App\Domain\ValueObjects\Isbn;
interface BookRepositoryInterface {
    public function findById(string $id): ?Book;
    public function findByIsbn(Isbn $isbn): ?Book;
    public function hasOpenLoans(string $bookId): bool;
    public function save(Book $book): void;
    /** @return array{items: list<Book>, total: int} */
    public function searchPaginated(?string $q, ?string $categoryId, int $page, int $size): array;
}