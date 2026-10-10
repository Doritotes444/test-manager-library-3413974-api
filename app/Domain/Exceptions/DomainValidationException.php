<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class DomainValidationException extends BusinessRuleViolation
{
    public function __construct(string $message = "Error de validaciÃ³n de datos de entrada.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "VALIDATION_ERROR";
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}