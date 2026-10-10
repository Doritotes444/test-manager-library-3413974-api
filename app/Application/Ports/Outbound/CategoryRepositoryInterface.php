<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
use App\Domain\Entities\Category;
interface CategoryRepositoryInterface {
    /** @return list<Category> */
    public function listAll(): array;
    public function findById(string $id): ?Category;
}