<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidDateRangeException;
use DateTimeImmutable;

final class DateRange
{
    public function __construct(
        private readonly DateTimeImmutable $from,
        private readonly DateTimeImmutable $to
    ) {
        if ($this->to < $this->from) {
            throw new InvalidDateRangeException("La fecha final no puede ser anterior a la inicial.");
        }
    }

    public static function of(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        return new self($from, $to);
    }

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): DateTimeImmutable
    {
        return $this->to;
    }
}