<?php

namespace Antevenio\Memoize;

use phpDocumentor\Reflection\Types\Object_;

class MemoizeTest extends \PHPUnit_Framework_TestCase
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
        $this->sut = new Memoize();
        $this->sut->flush();
    }

    public function testMemoizeShouldExecuteTheCallableTheFirstTimeItsCalled()
    {
        $mock = $this->getCallableMock();

        $memoizable = new Memoizable(
            [$mock, 'doit'],
            $this->arguments,
            0
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

        $memoizable = new Memoizable(
            [$mock, 'doit'],
            $this->arguments,
            0
        );

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

        $memoizable = new Memoizable(
            [$mock, 'doit'],
            $this->arguments,
            10
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
                new Memoizable([$mock, 'doit'], ['a', 'b'], 10)
            )
        );
        $this->assertEquals(
            'f',
            $this->sut->memoize(
                new Memoizable([$mock, 'doit'], ['d', 'e'], 10)
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
                new Memoizable([$mock, 'doit'], ['a', 'b'], 10),
                'key'
            )
        );
        $this->assertEquals(
            'c',
            $this->sut->memoize(
                new Memoizable([$mock, 'doit'], ['d', 'e'], 10),
                'key'
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
                new Memoizable([$mock, 'doit'], ['a', 'b'], 10),
                'key1'
            )
        );
        $this->assertEquals(
            'd',
            $this->sut->memoize(
                new Memoizable([$mock, 'doit'], ['a', 'b'], 10),
                'key2'
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
                new Memoizable([$mock, 'doit'], ['a', 'b'], 1)
            )
        );
        sleep(1);
        $this->assertEquals(
            'c',
            $this->sut->memoize(
                new Memoizable([$mock, 'doit'], ['a', 'b'], 1)
            )
        );
    }

    protected function getCallableMock()
    {
        return $this->getMockBuilder('object')
            ->setMethods(['doit'])
            ->getMock();
    }
}
