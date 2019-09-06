<?php
namespace Antevenio\Memoize;

use Psr\Log\LoggerInterface;

class Memoize
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected static $logger;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function setLogger(LoggerInterface $logger)
    {
        self::$logger = $logger;

        return $this;
    }

    /**
     * @param Memoizable $memoizable
     * @return mixed
     */
    public function memoize(Memoizable $memoizable)
    {
        if ($this->cache->exists($memoizable) && $this->cache->get($memoizable)->expired()) {
            $this->log("[ delete] " . $memoizable->getShortId());
            $this->cache->delete($memoizable);
        }

        if (!$this->cache->exists($memoizable)) {
            $memoizable->execute();
            $this->log("[execute] " . $memoizable->getShortId());
            $this->cache->set($memoizable);
            $this->log("[    set] " . $memoizable->getShortId());
        }

        $this->log("[    get] " . $memoizable->getShortId());
        return $this->cache->get($memoizable)->getResult();
    }

    public function getCache()
    {
        return $this->cache;
    }

    protected function log($message)
    {
        self::$logger->debug(
            "MMZ [" . sprintf('%04d', $this->cache->getNumberOfEntries()) . "]: $message"
        );
    }
}
