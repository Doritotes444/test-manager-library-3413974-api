<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class MemberLimitExceededException extends BusinessRuleViolation
{
    public function __construct(string $message = "El socio excede el lÃ­mite de 3 libros sin devolver en total.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "MEMBER_LIMIT_EXCEEDED";
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}