<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\MySQL;

class MySQLTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MySQL
     */
    protected $mutex;

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
        // Mock lock and release statements
        $this->stmtGetLock     = $this->getMock('\PDOStatement');
        $this->stmtReleaseLock = $this->getMock('\PDOStatement');

        // Constructor will prepare the statements
        /** @var \PDO|\PHPUnit_Framework_MockObject_MockObject $pdo */
        $pdo = $this->getMock('\Phlib\Mutex\Test\MockablePdo');
        $pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $pdo->expects($this->at(1))
            ->method('prepare')
            ->will($this->returnValue($this->stmtReleaseLock));

        $this->mutex = new MySQL($pdo);
    }

    public function testAcquire()
    {
        $lockName = 'dummyLock';

        $this->stmtGetLock->expects($this->once())
            ->method('execute')
            ->with(array($lockName, 0));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->acquire($lockName);

        $this->assertEquals(true, $result);
    }

    public function testAcquireTimeout()
    {
        $lockName = 'dummyLock';
        $lockTimeout = 30;

        $this->stmtGetLock->expects($this->once())
            ->method('execute')
            ->with(array($lockName, $lockTimeout));

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->acquire($lockName, $lockTimeout);

        $this->assertEquals(true, $result);
    }

    public function testAcquireFailed()
    {
        // Invalid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(0));

        $result = $this->mutex->acquire('dummyLock');

        $this->assertEquals(false, $result);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failure on mutex
     */
    public function testAcquireInvalidResult()
    {
        // Invalid lock result
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(2));

        $this->mutex->acquire('dummyLock');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failure on mutex
     */
    public function testAcquireError()
    {
        // stm fetchColumn gives no result

        $this->mutex->acquire('dummyLock');
    }

    public function testAcquireExisting()
    {
        $lockName = 'dummyLock';

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->acquire($lockName);

        // Now it's locked, another acquire should not execute the stm
        $this->stmtGetLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->acquire($lockName);

        $this->assertEquals(true, $result);
    }

    /**
     * @covers Mxm\Mutex\MySQL::release
     */
    public function testRelease()
    {
        $lockName = 'dummyLock';

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->acquire($lockName);

        // Valid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('execute')
            ->with(array($lockName));

        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->release($lockName);

        $this->assertEquals(true, $result);
    }

    /**
     * @covers Mxm\Mutex\MySQL::release
     */
    public function testReleaseFailed()
    {
        $lockName = 'dummyLock';

        // Valid lock
        $this->stmtGetLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $this->mutex->acquire($lockName);

        // Invalid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(0));

        $result = $this->mutex->release($lockName);

        $this->assertEquals(false, $result);
    }

    /**
     * @covers Mxm\Mutex\MySQL::release
     */
    public function testReleaseNoLock()
    {
        // The stm should not be executed
        $this->stmtReleaseLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->release('dummyLock');

        $this->assertEquals(false, $result);
    }
}
