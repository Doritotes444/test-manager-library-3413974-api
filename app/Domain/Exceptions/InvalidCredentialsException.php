<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class InvalidCredentialsException extends BusinessRuleViolation
{
    public function __construct(string $message = "Credenciales de acceso no vÃ¡lidas.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "INVALID_CREDENTIALS";
    }

    public function getHttpStatus(): int
    {
        return 401;
    }
}