<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class BookNotAvailableException extends BusinessRuleViolation
{
    public function __construct(string $message = "No quedan ejemplares disponibles del libro solicitado.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "BOOK_NOT_AVAILABLE";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}