<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class ForbiddenRoleException extends BusinessRuleViolation
{
    public function __construct(string $message = "No tiene permisos para realizar esta acciÃ³n.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "FORBIDDEN_ROLE";
    }

    public function getHttpStatus(): int
    {
        return 403;
    }
}