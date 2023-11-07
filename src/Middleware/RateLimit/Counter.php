<?php

namespace DMT\Http\Client\Middleware\RateLimit;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Counter
{
    public function __construct(public DateTimeInterface $expireTime, public int $count = 1)
    {
        $this->expireTime->setTimezone(new DateTimeZone('UTC'));
    }

    public function __serialize(): array
    {
        return [
            $this->count,
            $this->expireTime->format(DateTimeInterface::RFC7231)
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->count = $data[0];
        $this->expireTime = DateTimeImmutable::createFromFormat(
            DateTimeInterface::RFC7231,
            $data[1],
            new DateTimeZone('UTC')
        );
    }
}
