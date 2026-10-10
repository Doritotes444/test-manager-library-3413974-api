<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class DuplicateBookInLoanException extends BusinessRuleViolation
{
    public function __construct(string $message = "No se puede incluir el mismo libro mÃ¡s de una vez en el prÃ©stamo.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "DUPLICATE_BOOK_IN_LOAN";
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}