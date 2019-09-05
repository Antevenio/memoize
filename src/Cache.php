<?php

namespace Antevenio\Memoize;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Cache
{
    const UNLIMITED = -1;
    /**
     * @var Memoizable[]
     */
    protected static $cache = [];
    protected static $entryLimit = self::UNLIMITED;
    /**
     * @var LoggerInterface
     */
    protected static $logger;

    public function __construct()
    {
        $this->setLogger(new NullLogger());
    }

    public function setEntryLimit($limit)
    {
        self::$entryLimit = $limit;

        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;

        return $this;
    }

    /**
     * @param Memoizable $memoizable
     * @return bool
     */
    public function exists(Memoizable $memoizable)
    {
        $result = isset(self::$cache[$memoizable->getHash()]);
        $this->log(
            "Asking if " . $memoizable . " is cached => " .
            json_encode($result)
        );
        return $result;
    }

    protected function log($message)
    {
        self::$logger->debug($message);
        self::$logger->debug("Current cache entries: " .  count(self::$cache));
    }

    /**
     * @param Memoizable $memoizable
     * @return Memoizable
     */
    public function get(Memoizable $memoizable)
    {
        $result = self::$cache[$memoizable->getHash()];
        $this->log(
            "Getting " . $memoizable . " from cache"
        );
        return $result;
    }

    /**
     * @param Memoizable $memoizable
     */
    public function set(Memoizable $memoizable)
    {
        if (count(self::$cache) == self::$entryLimit) {
            $this->evictOldest();
        }

        $this->log(
            "Adding " . $memoizable . " to cache"
        );
        self::$cache[$memoizable->getHash()] = $memoizable;
    }

    public function delete(Memoizable $memoizable)
    {
        $this->log(
            "Deleting " . $memoizable . " from cache"
        );
        unset(self::$cache[$memoizable->getHash()]);
    }

    protected function evictOldest()
    {
        $memoizable = reset(self::$cache);
        $this->log("Evicting oldest entry from cache. (see next 'deleting' log)");
        $this->delete($memoizable);
    }

    public function flush()
    {
        $this->log("Flushing cache");
        self::$cache = [];
    }
}
