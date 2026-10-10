<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Application\Services\LendBooksService;
use App\Application\Services\ReturnItemService;
use App\Application\DTOs\LendBooksCommand;
use App\Application\Ports\Outbound\LoanRepositoryInterface;
use App\Application\Ports\Outbound\BookRepositoryInterface;
use App\Application\Ports\Outbound\MemberRepositoryInterface;
use App\Application\Ports\Outbound\ClockInterface;
use App\Domain\Entities\Book;
use App\Domain\Entities\Member;
use App\Domain\Entities\Loan;
use App\Domain\ValueObjects\Isbn;
use App\Domain\Exceptions\MemberLimitExceededException;
use App\Domain\Exceptions\DuplicateBookInLoanException;
use App\Domain\Exceptions\MemberHasOverdueLoansException;
use DateTimeImmutable;

final class ApplicationServicesTest extends TestCase
{
    public function test_cannot_lend_more_than_3_books(): void
    {
        $loanRepo = $this->createMock(LoanRepositoryInterface::class);
        $memberRepo = $this->createMock(MemberRepositoryInterface::class);
        $bookRepo = $this->createMock(BookRepositoryInterface::class);
        $clock = $this->createMock(ClockInterface::class);

        $member = new Member("m-1", "Carlos Gomez", "CC", "1020304050", "carlos@test.com", "3001234567");
        $memberRepo->method('findById')->willReturn($member);
        $loanRepo->method('hasOverdueLoans')->willReturn(false);
        $loanRepo->method('countActiveItemsByMember')->willReturn(2);

        $service = new LendBooksService($loanRepo, $memberRepo, $bookRepo, $clock);

        $this->expectException(MemberLimitExceededException::class);
        $cmd = new LendBooksCommand("m-1", ["b-1", "b-2"], "u-1");
        $service->lend($cmd);
    }

    public function test_cannot_lend_duplicate_books_in_same_operation(): void
    {
        $loanRepo = $this->createMock(LoanRepositoryInterface::class);
        $memberRepo = $this->createMock(MemberRepositoryInterface::class);
        $bookRepo = $this->createMock(BookRepositoryInterface::class);
        $clock = $this->createMock(ClockInterface::class);

        $member = new Member("m-1", "Carlos Gomez", "CC", "1020304050", "carlos@test.com", "3001234567");
        $memberRepo->method('findById')->willReturn($member);
        $loanRepo->method('hasOverdueLoans')->willReturn(false);
        $loanRepo->method('countActiveItemsByMember')->willReturn(0);

        $service = new LendBooksService($loanRepo, $memberRepo, $bookRepo, $clock);

        $this->expectException(DuplicateBookInLoanException::class);
        $cmd = new LendBooksCommand("m-1", ["b-1", "b-1"], "u-1");
        $service->lend($cmd);
    }

    public function test_cannot_lend_to_member_with_overdue_loans(): void
    {
        $loanRepo = $this->createMock(LoanRepositoryInterface::class);
        $memberRepo = $this->createMock(MemberRepositoryInterface::class);
        $bookRepo = $this->createMock(BookRepositoryInterface::class);
        $clock = $this->createMock(ClockInterface::class);

        $member = new Member("m-1", "Carlos Gomez", "CC", "1020304050", "carlos@test.com", "3001234567");
        $memberRepo->method('findById')->willReturn($member);
        $loanRepo->method('hasOverdueLoans')->willReturn(true);

        $service = new LendBooksService($loanRepo, $memberRepo, $bookRepo, $clock);

        $this->expectException(MemberHasOverdueLoansException::class);
        $cmd = new LendBooksCommand("m-1", ["b-1"], "u-1");
        $service->lend($cmd);
    }
}
