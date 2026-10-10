<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class IsbnTakenException extends BusinessRuleViolation
{
    public function __construct(string $message = "Ya existe un libro activo con el mismo ISBN.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "ISBN_TAKEN";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}