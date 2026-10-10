<?php

namespace App\Application\Ports\Outbound;

interface MostBorrowedReportQueryInterface
{
    /**
     * @return array<int, array{book_id: string, title: string, author: string, category_name: string, borrow_count: int}>
     */
    public function execute(int $limit = 10, ?string $from = null, ?string $to = null): array;
}
