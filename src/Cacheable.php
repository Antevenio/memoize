<?php
namespace Antevenio\Memoize;

class Cacheable
{
    protected $timestamp;
    protected $data;
    protected $usedMemory;
    protected $thrownException;

    /**
     * Cacheable constructor.
     * @param callable $callable
     * @param $arguments
     */
    public function __construct(callable $callable, $arguments)
    {
        $thrownException = null;
        $currentMemoryUsage = memory_get_usage();
        try {
            $this->data = call_user_func_array($callable, $arguments);
        } catch (\Exception $ex) {
            $this->thrownException = $ex;
        }
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

    public function getThrownException()
    {
        return $this->thrownException;
    }
}