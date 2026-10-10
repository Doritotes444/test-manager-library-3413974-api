<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\Outbound\UnitOfWorkInterface;
use Illuminate\Support\Facades\DB;

class EloquentUnitOfWork implements UnitOfWorkInterface
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transaction(callable $operation)
    {
        return DB::transaction($operation);
    }
}
