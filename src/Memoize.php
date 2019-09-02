<?php
namespace Antevenio\Memoize;

class Memoize
{
    const TTL = 60 * 5; // 5 minutes
    const MAX_MEMORY = 100 * 1024 * 1024;
    /**
     * @var Cacheable[]
     */
    static $cache = [];
    static $usedMemory = 0;

    /**
     * @param callable $callable
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function memoize(callable $callable, array $arguments = [])
    {
        $hash = self::computeHash($callable, $arguments);
        if (self::elementExists($hash) && self::elementExpired($hash)) {
            self::removeCacheEntry($hash);
        }
        if (!self::elementExists($hash)) {
            $element = new Cacheable($callable, $arguments);
            if ($element->getUsedMemory() > self::MAX_MEMORY) {
                return $this->getResult($element);
            }
            while (self::$usedMemory + $element->getUsedMemory() > self::MAX_MEMORY) {
                self::evictCacheEntry();
            }
            self::$usedMemory += $element->getUsedMemory();
            self::$cache[$hash] = $element;
        }

        return $this->getResult(self::$cache[$hash]);
    }

    /**
     * @param Cacheable $element
     * @return mixed
     * @throws \Exception
     */
    protected function getResult(Cacheable $element)
    {
        if ($exception = $element->getThrownException()) {
            throw $exception;
        }
        return $element->getData();
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
        return (time() - self::$cache[$hash]->getTimestamp()) > self::TTL;
    }

    protected static function computeHash(callable $callable, array $arguments)
    {
        return md5(json_encode($callable) . serialize($arguments));
    }

    public function flush()
    {
        self::$cache = null;
        self::$cache = [];
        self::$usedMemory = 0;
        gc_collect_cycles();
    }
}
