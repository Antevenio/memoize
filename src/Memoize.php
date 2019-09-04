<?php
namespace Antevenio\Memoize;

use Exception;

class Memoize
{
    const DEFAULT_MEMORY_LIMIT = 100 * 1024 * 1024;
    /**
     * @var Memoizable[]
     */
    protected static $cache = [];
    protected static $usedMemory = 0;
    protected static $memoryLimit = self::DEFAULT_MEMORY_LIMIT;

    /**
     * @param Memoizable $memoizable
     * @param mixed $userKey
     * @return mixed
     * @throws Exception
     */
    public function memoize(Memoizable $memoizable, $userKey = null)
    {
        $hash = $memoizable->computeHash($userKey);
        if (self::elementExists($hash) && self::elementExpired($hash)) {
            self::removeCacheEntry($hash);
        }
        if (!self::elementExists($hash)) {
            $memoizable->execute();
            if ($memoizable->getUsedMemory() > self::$memoryLimit) {
                return $memoizable->getResult();
            }

            while (self::$usedMemory + $memoizable->getUsedMemory() > self::$memoryLimit) {
                self::evictCacheEntry();
            }
            self::$usedMemory += $memoizable->getUsedMemory();
            self::$cache[$hash] = $memoizable;
        }
        return self::$cache[$hash]->getResult();
    }

    protected static function evictCacheEntry()
    {
        reset(self::$cache);
        self::removeCacheEntry(key(self::$cache));
    }

    protected static function removeCacheEntry($hash)
    {
        $element = self::$cache[$hash];
        self::$usedMemory -= $element->getUsedMemory();
        unset(self::$cache[$hash]);
    }

    protected static function elementExists($hash)
    {
        return isset(self::$cache[$hash]);
    }

    protected static function elementExpired($hash)
    {
        return (time() - self::$cache[$hash]->getTimestamp()) >= self::$cache[$hash]->getTtl();
    }

    public function setMemoryLimit($limit)
    {
        self::$memoryLimit = $limit;
    }

    public function flush()
    {
        self::$cache = null;
        self::$cache = [];
        self::$usedMemory = 0;
    }
}
