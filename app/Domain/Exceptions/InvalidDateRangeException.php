<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class InvalidDateRangeException extends BusinessRuleViolation
{
    public function __construct(string $message = "El rango de fechas es invÃ¡lido: la fecha final es anterior a la inicial.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "INVALID_DATE_RANGE";
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}