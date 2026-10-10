<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
use App\Domain\Entities\Member;
use DateTimeImmutable;
interface MemberRepositoryInterface {
    public function findById(string $id): ?Member;
    public function findByDocument(string $documentNumber): ?Member;
    public function getActiveLoansCount(string $memberId): int;
    public function hasOverdueLoans(string $memberId, DateTimeImmutable $now): bool;
    public function save(Member $member): void;
    /** @return array{items: list<Member>, total: int} */
    public function searchPaginated(?string $q, int $page, int $size): array;
}