<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\Helper;
use Phlib\Mutex\MutexInterface;
use Phlib\Mutex\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    const LOCK_NAME = 'dummyLock';

    /**
     * @var MutexInterface|MockObject
     */
    protected $mutex;

    protected function setUp()
    {
        // Mock Mutex
        $this->mutex = $this->createMock(MutexInterface::class);
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

    public function testGetOrCreateGetException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Get exception');

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

    public function testGetOrCreateGetFailThenException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Get exception');

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

    public function testGetOrCreateGetFailThenLocked()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to acquire lock on mutex');

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

    public function testGetOrCreateGetFailThenCreateUnlockFail()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to release lock on mutex');

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

    public function testGetOrCreateGetFailThenCreateException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Create exception');

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

    public function testGetOrCreateGetFailThenCreateNotFoundException()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Create not found exception');

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
