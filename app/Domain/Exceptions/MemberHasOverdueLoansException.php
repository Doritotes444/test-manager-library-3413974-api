<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class MemberHasOverdueLoansException extends BusinessRuleViolation
{
    public function __construct(string $message = "El socio tiene libros vencidos sin devolver y no puede solicitar nuevos prÃ©stamos.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "MEMBER_HAS_OVERDUE";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}