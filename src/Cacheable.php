<?php
namespace Antevenio\Memoize;

class Cacheable
{
    protected $timestamp;
    protected $data;
    protected $usedMemory;

    public function __construct(callable $callable, $arguments)
    {
        $currentMemoryUsage = memory_get_usage();
        $this->data = call_user_func_array($callable, $arguments);
        $this->usedMemory = (memory_get_usage() - $currentMemoryUsage);
        $this->timestamp = time();
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    public function getUsedMemory()
    {
        return $this->usedMemory;
    }
}