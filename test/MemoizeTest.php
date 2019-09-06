<?php
namespace Antevenio\Memoize\Test;

use Antevenio\Memoize\Cache;
use Antevenio\Memoize\Memoizable;
use Antevenio\Memoize\Memoize;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit_Framework_TestCase;
use stdClass;

// phpcs:disable
$callHistory = [];

function doit($argument1, $argument2)
{
    global $callHistory;

    $callHistory[] = ['doit', [$argument1, $argument2]];
}
// phpcs:enable

class MemoizeTest extends PHPUnit_Framework_TestCase
{
    protected $returnValue;
    protected $thrownException;
    protected $arguments;
    /**
     * @var Memoize
     */
    protected $sut;

    public function setUp()
    {
        $this->returnValue = 'some return value';
        $this->arguments = ['argument 1', 'argument 2'];

        $log = new Logger('log');
        $log->pushHandler(new StreamHandler('php://stdout'));

        $this->sut = new Memoize(new Cache());
        // Uncomment the below line to watch logging
        // $this->sut->setLogger($log);
        $this->sut->getCache()->flush();
    }

    public function testMemoizeShouldExecuteTheCallableTheFirstTimeItsCalled()
    {
        $mock = $this->getCallableMock();

        $memoizable = new Memoizable(
            [$mock, 'doit'],
            $this->arguments
        );

        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo($this->arguments[0]),
                $this->equalTo($this->arguments[1])
            )
            ->will($this->returnValue($this->returnValue));

        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
    }

    public function testMemoizeShouldExecuteTheCallableTwiceIfProvidedTtlIsZero()
    {
        $mock = $this->getCallableMock();

        $memoizable = (new Memoizable(
            [$mock, 'doit'],
            $this->arguments
        ))->withTtl(0);

        $mock->expects($this->exactly(2))
            ->method('doit')
            ->with(
                $this->equalTo($this->arguments[0]),
                $this->equalTo($this->arguments[1])
            )
            ->will($this->returnValue($this->returnValue));

        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
    }

    public function testMemoizeShouldExecuteTheCallableOnceIfTtlHigherThanZero()
    {
        $mock = $this->getCallableMock();

        $memoizable = (new Memoizable(
            [$mock, 'doit'],
            $this->arguments
        ))->withTtl(10);

        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo($this->arguments[0]),
                $this->equalTo($this->arguments[1])
            )
            ->will($this->returnValue($this->returnValue));

        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
    }

    public function testMemoizeShouldExecuteTheCallableOnceIfNoTtlIsSpecified()
    {
        $mock = $this->getCallableMock();

        $memoizable = new Memoizable(
            [$mock, 'doit'],
            $this->arguments
        );

        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo($this->arguments[0]),
                $this->equalTo($this->arguments[1])
            )
            ->will($this->returnValue($this->returnValue));

        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
    }

    public function testMemoizeShouldExecuteTheCallableOnceIfInfiniteTtlIsSpecified()
    {
        $mock = $this->getCallableMock();

        $memoizable = (new Memoizable(
            [$mock, 'doit'],
            $this->arguments
        ))->withTtl(Memoizable::TTL_INFINITE);

        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo($this->arguments[0]),
                $this->equalTo($this->arguments[1])
            )
            ->will($this->returnValue($this->returnValue));

        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
        $this->assertEquals($this->returnValue, $this->sut->memoize($memoizable));
    }

    public function testMemoizeShouldExecuteTheCallableTwiceOnDifferentArguments()
    {
        $mock = $this->getCallableMock();

        $mock->expects($this->at(0))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('c'));

        $mock->expects($this->at(1))
            ->method('doit')
            ->with(
                $this->equalTo('d'),
                $this->equalTo('e')
            )
            ->will($this->returnValue('f'));

        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(10)
            )
        );
        $this->assertEquals(
            'f',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['d', 'e']))->withTtl(10)
            )
        );
    }

    public function testMemoizeShouldExecuteTheCallableOnceOnDifferentArgumentsButSameUserKey()
    {
        $mock = $this->getCallableMock();

        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('c'));

        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(10)->withCustomIndex('key')
            )
        );
        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['d', 'e']))->withTtl(10)->withCustomIndex('key')
            )
        );
    }

    public function testMemoizeShouldExecuteTheCallableTwiceOnSameArgumentsButDifferentUserKey()
    {
        $mock = $this->getCallableMock();

        $mock->expects($this->at(0))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('c'));

        $mock->expects($this->at(1))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('d'));

        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(10)->withCustomIndex('key1')
            )
        );
        $this->assertEquals(
            'd',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(10)->withCustomIndex('key2')
            )
        );
    }

    public function testMemoizeShouldExcecuteTheCallableAgainAfterTtlPassed()
    {
        $mock = $this->getCallableMock();

        $mock->expects($this->at(0))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('c'));

        $mock->expects($this->at(1))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will($this->returnValue('c'));
        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(1)
            )
        );
        sleep(1);
        $this->assertEquals(
            'c',
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(1)
            )
        );
    }

    public function testMemoizeShouldEvictTheFirstKeyWhenFull()
    {
        /** @var Memoizable[] $memoizables */
        $memoizables = [
            (new Memoizable([$this->getCallableMock(), 'doit'], ['a', 'b']))->withTtl(100),
            (new Memoizable([$this->getCallableMock(), 'doit'], ['a', 'b']))->withTtl(100),
            (new Memoizable([$this->getCallableMock(), 'doit'], ['a', 'b']))->withTtl(100),
        ];

        $this->sut->getCache()->setEntryLimit(2);
        /** @var \PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $memoizables[0]->getCallable()[0];
        $mock->expects($this->exactly(2))
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            );

        foreach ($memoizables as $memoizable) {
            $this->sut->memoize($memoizable);
        }

        $this->sut->memoize($memoizables[0]);
    }

    public function testMemoizeShouldCacheCallableExceptions()
    {
        $thrownException = new Exception('some message');
        $mock = $this->getCallableMock();
        $mock->expects($this->once())
            ->method('doit')
            ->with(
                $this->equalTo('a'),
                $this->equalTo('b')
            )
            ->will(
                $this->throwException($thrownException)
            );

        try {
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(100)
            );
        } catch (Exception $ex) {
            $this->assertEquals($ex->getMessage(), $thrownException->getMessage());
            return;
        }

        try {
            $this->sut->memoize(
                (new Memoizable([$mock, 'doit'], ['a', 'b']))->withTtl(100)
            );
        } catch (Exception $ex) {
            $this->assertEquals($ex->getMessage(), $thrownException->getMessage());
            return;
        }
        $this->assertFalse(true);
    }

    public function testMemoizeShouldCacheStaticCallablesInArrayNotation()
    {
        TestSubject::resetStaticCallHistory();
        $this->sut->memoize(
            new Memoizable([TestSubject::class, 'doit'], ['a', 'b'])
        );
        $this->assertCount(1, TestSubject::getStaticCallHistory());
        $this->assertEquals(['doit', ['a', 'b']], TestSubject::getStaticCallHistory()[0]);
    }

    public function testMemoizeShouldCacheStaticCallablesInStringNotation()
    {
        TestSubject::resetStaticCallHistory();
        $this->sut->memoize(
            new Memoizable(TestSubject::class . '::doit', ['a', 'b'])
        );
        $this->assertCount(1, TestSubject::getStaticCallHistory());
        $this->assertEquals(['doit', ['a', 'b']], TestSubject::getStaticCallHistory()[0]);
    }

    public function testMemoizeShouldCacheGlobalFunctionCallables()
    {
        global $callHistory;

        $callHistory = [];
        $this->sut->memoize(
            new Memoizable('Antevenio\Memoize\Test\doit', ['a', 'b'])
        );
        $this->assertCount(1, $callHistory);
        $this->assertEquals(['doit', ['a', 'b']], $callHistory[0]);
    }

    public function testMemoizeShouldCacheInvokeFunctionsFromObjects()
    {
        $testSubject = new TestSubject();
        $this->sut->memoize(
            new Memoizable($testSubject, ['a', 'b'])
        );
        $this->assertCount(1, $testSubject->getCallHistory());
        $this->assertEquals(['doit', ['a', 'b']], $testSubject->getCallHistory()[0]);
    }

    protected function getCallableMock()
    {
        return $this->getMockBuilder(stdClass::class)
            ->setMethods(['doit'])
            ->getMock();
    }
}
