<?php
namespace Antevenio\Memoize;

use Exception;
use function Opis\Closure\serialize as serialize;

class Memoizable
{
    const TTL_INFINITE = -1;

    protected $timestamp;
    protected $result;
    protected $thrownException;
    protected $ttl;
    protected $arguments;
    protected $callable;
    protected $customIndex;

    /**
     * Memoizable constructor.
     * @param callable $callable
     * @param $arguments
     */
    public function __construct(callable $callable, array $arguments = [])
    {
        $this->ttl = self::TTL_INFINITE;
        $this->customIndex = null;
        $this->arguments = $arguments;
        $this->callable = $callable;
    }

    public function withTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function withCustomIndex($customIndex)
    {
        $this->customIndex = $customIndex;

        return $this;
    }

    public function execute()
    {
        $this->timestamp = time();
        $this->thrownException = null;
        try {
            $this->result = call_user_func_array($this->getCallable(), $this->getArguments());
        } catch (Exception $ex) {
            $this->thrownException = $ex;
        }
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getThrownException()
    {
        return $this->thrownException;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    public function expired()
    {
        if ($this->getTtl() == self::TTL_INFINITE) {
            return false;
        } else {
            return (time() - $this->getTimestamp()) >= $this->getTtl();
        }
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function __toString()
    {
        $argumentKey = $this->getArguments();
        if ($this->customIndex != null) {
            $argumentKey = $this->customIndex;
        }
        return $this->getCallableId($this->callable) . '::' . serialize($argumentKey);
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getHash()
    {
        return md5($this->__toString());
    }

    protected function getCallableId(callable $callable)
    {
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                return get_class($callable[0]) . '::' .
                    spl_object_hash($callable[0]) . '::' .
                    $callable[1];
            } else {
                return $callable[0] . '::' . $callable[1];
            }
        } else {
            if (is_object($callable)) {
                return get_class($callable) . '::' . spl_object_hash($callable);
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

    public function getShortId()
    {
        return mb_strimwidth($this->__toString(), 0, 80, "...");
    }
}
