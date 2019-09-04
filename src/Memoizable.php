<?php
namespace Antevenio\Memoize;

class Memoizable
{
    protected $timestamp;
    protected $result;
    protected $thrownException;
    protected $ttl;
    protected $arguments;
    protected $callable;
    protected $usedMemory;

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
        try {
            $this->result = call_user_func_array($this->getCallable(), $this->getArguments());
        } catch (\Exception $ex) {
            $this->thrownException = $ex;
        }
        $this->calculateUsedMemory();
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function calculateUsedMemory()
    {
        $serialized = serialize($this);
        $old = memory_get_usage();
        $dummy = unserialize($serialized);
        $mem = memory_get_usage();
        // It seems like dividing this by 2 nails the memory consumption math
        $this->usedMemory = abs(($mem - $old)/2);

        return $this->usedMemory;
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

        return md5($this->serializeCallable($this->getCallable()) . serialize($argumentKey));
    }

    protected function serializeCallable(callable $callable)
    {
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                return spl_object_hash($callable[0]) . '::' . $callable[1];
            } else {
                return $callable[0] . '::' . $callable[1];
            }
        } else {
            if (is_object($callable)) {
                return spl_object_hash($callable);
            } else {
                return $callable;
            }
        }
    }

    public function getResult()
    {
        if ($exception = $this->getThrownException()) {
            throw $exception;
        }
        return $this->result;
    }
}
