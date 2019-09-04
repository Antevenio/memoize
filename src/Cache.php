<?php

namespace Antevenio\Memoize;

class Cache
{
    const DEFAULT_MEMORY_LIMIT = 100 * 1024 * 1024;
    /**
     * @var Memoizable[]
     */
    protected static $cache = [];
    protected static $usedMemory = 0;
    protected static $memoryLimit = self::DEFAULT_MEMORY_LIMIT;

    public function withMemoryLimit($limit)
    {
        self::$memoryLimit = $limit;

        return $this;
    }

    /**
     * @param Memoizable $memoizable
     * @return bool
     */
    public function exists(Memoizable $memoizable)
    {
        return isset(self::$cache[$memoizable->getHash()]);
    }

    /**
     * @param Memoizable $memoizable
     * @return Memoizable
     */
    public function get(Memoizable $memoizable)
    {
        return self::$cache[$memoizable->getHash()];
    }

    /**
     * @param $hash
     * @param Memoizable $memoizable
     */
    public function set(Memoizable $memoizable)
    {
        while (self::$usedMemory + $memoizable->getUsedMemory() > self::$memoryLimit) {
            $this->evictOldest();
        }
        self::$usedMemory += $memoizable->getUsedMemory();
        self::$cache[$memoizable->getHash()] = $memoizable;
    }

    public function delete(Memoizable $memoizable)
    {
        self::$usedMemory -= $this->get($memoizable)->getUsedMemory();
        unset(self::$cache[$memoizable->getHash()]);
    }

    protected function evictOldest()
    {
        $this->delete(reset(self::$cache));
    }

    public function fits(Memoizable $memoizable)
    {
        return ($memoizable->getUsedMemory() <= self::$memoryLimit);
    }

    public function flush()
    {
        self::$cache = null;
        self::$cache = [];
        self::$usedMemory = 0;
    }
}
