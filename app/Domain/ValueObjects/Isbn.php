<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\DomainValidationException;

final class Isbn
{
    private string $value;

    public function __construct(string $value)
    {
        $clean = trim($value);
        if (preg_match('/^\d{13}$/', $clean) !== 1) {
            throw new DomainValidationException("El ISBN debe tener exactamente 13 dÃ­gitos numÃ©ricos.");
        }
        $this->value = $clean;
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}