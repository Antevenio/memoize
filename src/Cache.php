<?php

namespace Antevenio\Memoize;

class Cache
{
    const UNLIMITED = -1;
    /**
     * @var Memoizable[]
     */
    protected static $cache = [];
    protected static $entryLimit = self::UNLIMITED;

    public function setEntryLimit($limit)
    {
        self::$entryLimit = $limit;

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
     * @param Memoizable $memoizable
     */
    public function set(Memoizable $memoizable)
    {
        if (count(self::$cache) == self::$entryLimit) {
            $this->evictOldest();
        }

        self::$cache[$memoizable->getHash()] = $memoizable;
    }

    public function delete(Memoizable $memoizable)
    {
        unset(self::$cache[$memoizable->getHash()]);
    }

    protected function evictOldest()
    {
        $this->delete(reset(self::$cache));
    }

    public function flush()
    {
        self::$cache = [];
    }
}
