<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class DocumentTakenException extends BusinessRuleViolation
{
    public function __construct(string $message = "El documento del socio ya se encuentra registrado.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "DOCUMENT_TAKEN";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}