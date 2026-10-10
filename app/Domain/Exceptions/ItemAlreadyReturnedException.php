<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class ItemAlreadyReturnedException extends BusinessRuleViolation
{
    public function __construct(string $message = "La lÃ­nea de prÃ©stamo ya fue devuelta previamente.")
    {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return "ITEM_ALREADY_RETURNED";
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}