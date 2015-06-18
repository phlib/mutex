<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\Helper;
use Phlib\Mutex\MutexInterface;
use Phlib\Mutex\NotFoundException;

class HelperTest extends \PHPUnit_Framework_TestCase
{
    const LOCK_NAME = 'dummyLock';

    /**
     * @var MutexInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mutex;

    protected function setUp()
    {
        // Mock Mutex
        $this->mutex = $this->getMockBuilder('\Phlib\Mutex\MutexInterface')
            ->getMock();
    }

    public function testGetOrCreateGetValid()
    {
        $expected = 'valid';

        $getClosure = function() use ($expected) {
            return $expected;
        };
        $createClosure = function() {
            $this->fail('Create Closure was not expected to be called');
        };

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        $this->assertEquals($expected, $result);
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
            $this->fail('Create Closure was not expected to be called');
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
                    return $expected;
                case 2 :
                    throw new NotFoundException('Value not found');
                default :
                    $this->fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            $this->fail('Create Closure was not expected to be called');
        };

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        $this->assertEquals($expected, $result);
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
                    $this->fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() {
            $this->fail('Create Closure was not expected to be called');
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
                    $this->fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() use ($expected) {
            $this->mutex->expects($this->at(1))
                ->method('unlock');
            ;
            return $expected;
        };

        $this->mutex->expects($this->at(0))
            ->method('lock')
            ->with(0) // Default value
        ;
        $this->mutex->expects($this->at(1))
            ->method('unlock')
            ->will($this->throwException(new \PHPUnit_Framework_AssertionFailedError('Unlock should not be called')));
        ;

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure);

        $this->assertEquals($expected, $result);
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
                    $this->fail('Get Closure was not expected to be called more than twice');
                    return null;
                    break;
            }
        };
        $createClosure = function() use ($expected) {
            return $expected;
        };

        $wait = 10;
        $this->mutex->expects($this->at(0))
            ->method('lock')
            ->with($wait)
        ;
        $this->mutex->expects($this->at(1))
            ->method('unlock')
        ;

        $result = Helper::getOrCreate($this->mutex, $getClosure, $createClosure, $wait);

        $this->assertEquals($expected, $result);
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
                    $this->fail('Get Closure was not expected to be called more than twice');
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
                    $this->fail('Get Closure was not expected to be called more than twice');
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
