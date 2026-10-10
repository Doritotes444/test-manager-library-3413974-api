<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class BookHasOpenLoansException extends BusinessRuleViolation
{
    public function __construct(string $message = "No se puede dar de baja el libro porque tiene prÃ©stamos abiertos.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "BOOK_HAS_OPEN_LOANS";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}