<?php

namespace DMT\Test\Http\Client\Stubs;

use DateTime;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

class CacheStub implements CacheInterface
{
    private array $storage = [];
    private array $ttl = [];

    public function get($key, $default = null)
    {
        if (!isset($this->ttl[$key]) || $this->ttl[$key] < new DateTime()) {
            return $default;
        }

        return $this->storage[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->ttl[$key] = (new DateTime())->add($ttl);
        $this->storage[$key] = $value;
    }

    public function delete($key)
    {
        if (isset($this->ttl[$key])) {
            unset($this->ttl[$key]);
        }

        unset($this->storage[$key]);
    }

    public function clear()
    {
        throw new RuntimeException("not implemented");
    }

    public function getMultiple($keys, $default = null)
    {
        throw new RuntimeException("not implemented");
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new RuntimeException("not implemented");
    }

    public function deleteMultiple($keys)
    {
        throw new RuntimeException("not implemented");
    }

    public function has($key)
    {
        throw new RuntimeException("not implemented");
    }
}
