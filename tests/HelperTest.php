<?php

declare(strict_types=1);

namespace Phlib\Mutex\Test;

use Phlib\Mutex\Helper;
use Phlib\Mutex\MutexInterface;
use Phlib\Mutex\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /**
     * @var MutexInterface|MockObject
     */
    protected $mutex;

    protected function setUp(): void
    {
        // Mock Mutex
        $this->mutex = $this->createMock(MutexInterface::class);
    }

    public function testGetOrCreateGetValid(): void
    {
        $expected = 'valid';

        $getClosure = function () use ($expected): string {
            return $expected;
        };
        $createClosure = function (): void {
            static::fail('Create Closure was not expected to be called');
        };

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        static::assertSame($expected, $result);
    }

    public function testGetOrCreateGetException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Get exception');

        $getClosure = function (): void {
            throw new \Exception('Get exception');
        };
        $createClosure = function (): void {
            static::fail('Create Closure was not expected to be called');
        };

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenValid(): void
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function () use ($expected, &$count) {
            switch (++$count) {
                case 1:
                    throw new NotFoundException('Value not found');
                case 2:
                    return $expected;
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
            }
        };
        $createClosure = function (): void {
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

        static::assertSame($expected, $result);
    }

    public function testGetOrCreateGetFailThenException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Get exception');

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                    throw new NotFoundException('Value not found');
                case 2:
                    throw new \Exception('Get exception');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function (): void {
            static::fail('Create Closure was not expected to be called');
        };

        $this->mutex->expects(static::once())
            ->method('lock')
            ->willReturn(true);

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenCreate(): void
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function () use ($expected): string {
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

        static::assertSame($expected, $result);
    }

    public function testGetOrCreateGetFailThenCreateWait(): void
    {
        $expected = 'valid';

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function () use ($expected): string {
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

        static::assertSame($expected, $result);
    }

    public function testGetOrCreateGetFailThenLocked(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to acquire lock on mutex');

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function (): void {
            static::fail('Create Closure was not expected to be called');
        };

        $this->mutex->expects(static::at(0))
            ->method('lock')
            ->with(0) // Default value
            ->willReturn(false)
        ;

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenCreateUnlockFail(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to release lock on mutex');

        $expected = 'valid';

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function () use ($expected): string {
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

    public function testGetOrCreateGetFailThenCreateException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Create exception');

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function (): void {
            throw new \Exception('Create exception');
        };

        $this->mutex->expects(static::once())
            ->method('lock')
            ->willReturn(true);

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }

    public function testGetOrCreateGetFailThenCreateNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Create not found exception');

        $count = 0;
        $getClosure = function () use (&$count) {
            switch (++$count) {
                case 1:
                case 2:
                    throw new NotFoundException('Value not found');
                default:
                    static::fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function (): void {
            throw new NotFoundException('Create not found exception');
        };

        $this->mutex->expects(static::once())
            ->method('lock')
            ->willReturn(true);

        Helper::getOrCreate($this->mutex, $getClosure, $createClosure);
    }
}
