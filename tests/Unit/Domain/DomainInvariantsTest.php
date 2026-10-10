<?php
declare(strict_types=1);
namespace Tests\Unit\Domain;

use App\Domain\Entities\Book;
use App\Domain\Entities\Loan;
use App\Domain\Entities\Member;
use App\Domain\Entities\User;
use App\Domain\Exceptions\BookNotAvailableException;
use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\ForbiddenRoleException;
use App\Domain\Exceptions\DomainValidationException;
use App\Domain\ValueObjects\DateRange;
use App\Domain\ValueObjects\Isbn;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DomainInvariantsTest extends TestCase {
    public function test_isbn_must_have_exactly_13_digits(): void {
        $isbn = Isbn::of("9780307474728");
        $this->assertSame("9780307474728", $isbn->value());

        $this->expectException(DomainValidationException::class);
        Isbn::of("123");
    }

    public function test_book_copies_invariant_available_between_0_and_total(): void {
        $book = Book::create(
            "b-1", "Cien Años de Soledad", "García Márquez",
            Isbn::of("9780307474728"), "cat-1", 2
        );

        $this->assertSame(2, $book->availableCopies());
        $book->withdrawCopy();
        $this->assertSame(1, $book->availableCopies());
        $book->withdrawCopy();
        $this->assertSame(0, $book->availableCopies());

        $this->expectException(BookNotAvailableException::class);
        $book->withdrawCopy();
    }

    public function test_loan_cannot_contain_duplicate_books(): void {
        $now = new DateTimeImmutable('2026-10-10T10:00:00Z');
        $loan = Loan::open("l-1", "m-1", "u-1", $now);
        $book = Book::create("b-1", "Libro A", "Autor A", Isbn::of("9780307474728"), "cat-1", 5);

        $loan->addItem("item-1", $book, "Novela");

        $this->expectException(DuplicateBookInLoanException::class);
        $loan->addItem("item-2", $book, "Novela");
    }

    public function test_username_is_normalized_to_lowercase_trimmed(): void {
        $norm = User::normalizeUsername("  AnaMaria  ");
        $this->assertSame("anamaria", $norm);
    }

    public function test_user_rejects_invalid_roles(): void {
        $this->expectException(ForbiddenRoleException::class);
        new User("u-1", "test", "hash", "manager");
    }
}