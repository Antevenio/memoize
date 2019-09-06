<?php
namespace Antevenio\Memoize\Test;

class TestSubject
{
    protected static $staticCallHistory = [];
    protected $callHistory = [];

    public function __invoke($argument1, $argument2)
    {
        $this->callHistory[] = ['doit', [$argument1, $argument2]];
    }

    public static function doit($argument1, $argument2)
    {
        self::$staticCallHistory[] = ['doit', [$argument1, $argument2]];
    }

    public static function getStaticCallHistory()
    {
        return self::$staticCallHistory;
    }

    public function getCallHistory()
    {
        return $this->callHistory;
    }

    public static function resetStaticCallHistory()
    {
        self::$staticCallHistory = [];
    }
}
