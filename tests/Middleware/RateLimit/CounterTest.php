<?php

namespace DMT\Test\Http\Client\Middleware\RateLimit;

use DateTime;
use DMT\Http\Client\Middleware\RateLimit\Counter;
use PHPUnit\Framework\TestCase;

class CounterTest extends TestCase
{
    public function testCounter(): void
    {
        $ttl = 30;
        $date = new DateTime(date('Y-m-d H:i:s.000', ceil(microtime(true)) + $ttl));

        $counter = new Counter($date);
        $deserializedCounter = unserialize(serialize($counter));

        $this->assertEquals($counter, $deserializedCounter);
        $this->assertEquals($date, $counter->expireTime);
        $this->assertEquals($date, $deserializedCounter->expireTime);
    }
}
