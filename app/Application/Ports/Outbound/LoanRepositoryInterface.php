<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
use App\Domain\Entities\Loan;
use App\Domain\Entities\LoanItem;
use DateTimeImmutable;
interface LoanRepositoryInterface {
    public function findById(string $id): ?Loan;
    public function save(Loan $loan): void;
    public function saveItem(LoanItem $item): void;
    /** @return array{items: list<Loan>, total: int} */
    public function searchPaginated(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?string $memberId,
        int $page,
        int $size
    ): array;
    /** @return list<array{loan_id: string, item_id: string, book_title: string, member_name: string, due_at: string, days_late: int}> */
    public function getOverdueItems(DateTimeImmutable $now): array;
}