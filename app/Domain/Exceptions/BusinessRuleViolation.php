<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

use Exception;

abstract class BusinessRuleViolation extends Exception
{
    abstract public function getErrorCode(): string;
    abstract public function getHttpStatus(): int;
}