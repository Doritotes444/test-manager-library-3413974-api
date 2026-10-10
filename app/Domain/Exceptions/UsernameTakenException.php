<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class UsernameTakenException extends BusinessRuleViolation
{
    public function __construct(string $message = "El nombre de usuario ya se encuentra registrado.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "USERNAME_TAKEN";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}