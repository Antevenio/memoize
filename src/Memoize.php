<?php
namespace Antevenio\Memoize;

class Memoize
{
    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param Memoizable $memoizable
     * @return mixed
     */
    public function memoize(Memoizable $memoizable)
    {
        if ($this->cache->exists($memoizable) && $this->cache->get($memoizable)->expired()) {
            $this->cache->delete($memoizable);
        }

        if (!$this->cache->exists($memoizable)) {
            $memoizable->execute();
            $this->cache->set($memoizable);
        }

        return $this->cache->get($memoizable)->getResult();
    }

    public function getCache()
    {
        return $this->cache;
    }
}
