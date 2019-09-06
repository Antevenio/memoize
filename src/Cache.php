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
        if (!$result) {
            $this->log("$memoizable executed");
        }
        return $result;
    }

    protected function log($message)
    {
        self::$logger->debug("MMZ [" . sprintf('%04d', count(self::$cache)) . "]: $message");
    }

    /**
     * @param Memoizable $memoizable
     * @return Memoizable
     */
    public function get(Memoizable $memoizable)
    {
        $result = self::$cache[$memoizable->getHash()];
        $this->log("$memoizable read from cache");

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

        $this->log("$memoizable added to cache");
        self::$cache[$memoizable->getHash()] = $memoizable;
    }

    public function delete(Memoizable $memoizable)
    {
        $this->log("$memoizable deleted from cache");
        unset(self::$cache[$memoizable->getHash()]);
    }

    protected function evictOldest()
    {
        $memoizable = reset(self::$cache);
        $this->log("evicting oldest cache entry");
        $this->delete($memoizable);
    }

    public function flush()
    {
        $this->log("flushing cache");
        self::$cache = [];
    }
}
