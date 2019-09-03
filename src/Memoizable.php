<?php
namespace Antevenio\Memoize;

class Memoizable
{
    protected $timestamp;
    protected $data;
    protected $usedMemory;
    protected $thrownException;
    protected $ttl;
    protected $arguments;
    protected $callable;

    /**
     * Memoizable constructor.
     * @param callable $callable
     * @param $arguments
     * @param int $ttl
     */
    public function __construct(callable $callable, $arguments, $ttl)
    {
        $this->arguments = $arguments;
        $this->ttl = $ttl;
        $this->callable = $callable;
    }

    public function execute()
    {
        $this->timestamp = time();
        $this->thrownException = null;
        $currentMemoryUsage = memory_get_usage();
        try {
            $this->data = call_user_func_array($this->getCallable(), $this->getArguments());
        } catch (\Exception $ex) {
            $this->thrownException = $ex;
        }
        $this->usedMemory = (memory_get_usage() - $currentMemoryUsage);
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

    public function getTtl()
    {
        return $this->ttl;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function computeHash($customArgumentKey = null)
    {
        $argumentKey = $this->getArguments();
        if ($customArgumentKey != null) {
            $argumentKey = $customArgumentKey;
        }
        return md5(json_encode($this->getCallable()) . serialize($argumentKey));
    }

    public function getResult()
    {
        if ($exception = $this->getThrownException()) {
            throw $exception;
        }
        return $this->getData();
    }
}
