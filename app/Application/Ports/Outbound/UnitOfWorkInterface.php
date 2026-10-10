<?php
declare(strict_types=1);
namespace App\Application\Ports\Outbound;
interface UnitOfWorkInterface {
    /** @template T
     *  @param callable(): T $operation
     *  @return T
     */
    public function transaction(callable $operation);
}