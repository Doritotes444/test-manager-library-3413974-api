<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\DomainValidationException;

final class Quantity
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value < 1) {
            throw new DomainValidationException("La cantidad debe ser mayor o igual a 1.");
        }
        $this->value = $value;
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}