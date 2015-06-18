<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\MySQL;

class MySQLTest extends \PHPUnit_Framework_TestCase
{
    const LOCK_NAME = 'dummyLock';

    /**
     * @var MySQL|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mutex;

    /**
     * @var \PDO|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pdo;

    /**
     * @var \PDOStatement|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stmtGetLock;

    /**
     * @var \PDOStatement|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stmtReleaseLock;

    protected function setUp()
    {
        // Mock PDO classes
        $this->pdo = $this->getMock('\Phlib\Mutex\Test\MockablePdo');
        $this->stmtGetLock     = $this->getMock('\PDOStatement');
        $this->stmtReleaseLock = $this->getMock('\PDOStatement');

        $this->mutex = $this->getMockBuilder('\Phlib\Mutex\MySQL')
            ->setConstructorArgs([self::LOCK_NAME, []])
            ->setMethods(['getConnection'])
            ->getMock();
    }

    public function testLock()
    {
        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        $this->stmtGetLock->expects($this->once())
            ->method('execute')
            ->with(array(self::LOCK_NAME, 0));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->lock();

        $this->assertEquals(true, $result);
    }

    public function testLockTimeout()
    {
        $lockTimeout = 30;

        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        $this->stmtGetLock->expects($this->once())
            ->method('execute')
            ->with(array(self::LOCK_NAME, $lockTimeout));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->lock($lockTimeout);

        $this->assertEquals(true, $result);
    }

    public function testLockFailed()
    {
        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // Invalid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(0));

        $result = $this->mutex->lock();

        $this->assertEquals(false, $result);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failure on mutex
     */
    public function testLockInvalidResult()
    {
        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // Invalid lock result
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(2));

        $this->mutex->lock();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failure on mutex
     */
    public function testLockError()
    {
        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // stm fetchColumn gives no result
        $this->mutex->lock();
    }

    public function testLockExisting()
    {
        $this->mutex->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->lock();

        // Now it's locked, another acquire should not execute the stm
        $this->stmtGetLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->lock();

        $this->assertEquals(true, $result);
    }

    public function testUnlock()
    {
        $this->mutex->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $this->pdo->expects($this->at(1))
            ->method('prepare')
            ->will($this->returnValue($this->stmtReleaseLock));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->lock();

        // Valid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('execute')
            ->with(array(self::LOCK_NAME));

        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->unlock();

        $this->assertEquals(true, $result);
    }

    public function testUnlockFailed()
    {
        $this->mutex->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $this->pdo->expects($this->at(1))
            ->method('prepare')
            ->will($this->returnValue($this->stmtReleaseLock));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->lock();

        // Invalid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(0));

        $result = $this->mutex->unlock();

        $this->assertEquals(false, $result);
    }

    public function testUnlockNoLock()
    {
        $this->mutex->expects($this->never())
            ->method('getConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->never())
            ->method('prepare');

        // The stm should not be executed
        $this->stmtReleaseLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->unlock();

        $this->assertEquals(false, $result);
    }
}
