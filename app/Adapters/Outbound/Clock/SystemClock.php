<?php

namespace App\Adapters\Outbound\Clock;

use App\Application\Ports\Outbound\ClockInterface;
use DateTimeImmutable;

class SystemClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
