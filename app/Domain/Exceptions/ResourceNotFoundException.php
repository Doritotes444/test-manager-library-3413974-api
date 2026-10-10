<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class ResourceNotFoundException extends BusinessRuleViolation
{
    public function __construct(string $message = "El recurso solicitado no fue encontrado o estÃ¡ dado de baja.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "NOT_FOUND";
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}