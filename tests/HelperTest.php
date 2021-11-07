<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\Helper;
use Phlib\Mutex\MutexInterface;
use Phlib\Mutex\NotFoundException;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    const LOCK_NAME = 'dummyLock';

    /**
     * @var MutexInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mutex;

    protected function setUp()
    {
        // Mock Mutex
        $this->mutex = $this->getMockBuilder(MutexInterface::class)
            ->getMock();
    }

    public function testGetOrCreateGetValid()
    {
        $expected = 'valid';

        $getClosure = function() use ($expected) {
            return $expected;
        };
        $createClosure = function() {
            static::fail('Create Closure was not expected to be called');
        };

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        static::assertEquals($expected, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Get exception
     */
    public function testGetOrCreateGetException()
    {
        $getClosure = function() {
            throw new \Exception('Get exception');
        };
        $createClosure = function() {
            static::fail('Create Closure was not expected to be called');
        };

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenValid()
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function() use ($expected, &$count) {
            switch (++$count) {
                case 1 :
                    throw new NotFoundException('Value not found');
                case 2 :
                    return $expected;
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
            }
        };
        $createClosure = function() {
            static::fail('Create Closure was not expected to be called');
        };

        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with(0) // Default value
            ->willReturn(true)
        ;
        $this->mutex->expects(static::at(1))
            ->method('unlock')
            ->willReturn(true)
        ;

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        static::assertEquals($expected, $result);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Get exception
     */
    public function testGetOrCreateGetFailThenException()
    {
        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    throw new NotFoundException('Value not found');
                case 2 :
                    throw new \Exception('Get exception');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            static::fail('Create Closure was not expected to be called');
        };

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenCreate()
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() use ($expected) {
            return $expected;
        };

        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with(0) // Default value
            ->willReturn(true)
        ;
        $this->mutex->expects(static::at(1))
            ->method('unlock')
            ->willReturn(true)
        ;

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        static::assertEquals($expected, $result);
    }

    public function testGetOrCreateGetFailThenCreateWait()
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() use ($expected) {
            return $expected;
        };

        $wait = 10;
        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with($wait)
            ->willReturn(true)
        ;
        $this->mutex->expects(static::at(1))
            ->method('unlock')
            ->willReturn(true)
        ;

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure, $wait);

        static::assertEquals($expected, $result);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to acquire lock on mutex
     */
    public function testGetOrCreateGetFailThenLocked()
    {
        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            static::fail('Create Closure was not expected to be called');
        };

        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with(0) // Default value
            ->willReturn(false)
        ;

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to release lock on mutex
     */
    public function testGetOrCreateGetFailThenCreateUnlockFail()
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() use ($expected) {
            return $expected;
        };

        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with(0) // Default value
            ->willReturn(true)
        ;
        $this->mutex->expects(static::at(1))
            ->method('unlock')
            ->willReturn(false)
        ;

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Create exception
     */
    public function testGetOrCreateGetFailThenCreateException()
    {
        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            throw new \Exception('Create exception');
        };

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    /**
     * @expectedException \Phlib\Mutex\NotFoundException
     * @expectedExceptionMessage Create not found exception
     */
    public function testGetOrCreateGetFailThenCreateNotFoundException()
    {
        $count = 0;
        $getClosure = function() use (&$count) {
            switch (++$count) {
                case 1 :
                    // no break
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            throw new NotFoundException('Create not found exception');
        };

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }
}
