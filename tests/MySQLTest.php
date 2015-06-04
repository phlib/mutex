<?php

namespace Phlib\Mutex\Test;

use Phlib\Mutex\MySQL;

class MySQLTest extends \PHPUnit_Framework_TestCase
{
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
            ->setConstructorArgs([[]])
            ->setMethods(['createConnection'])
            ->getMock();
    }

    public function testAcquire()
    {
        $lockName = 'dummyLock';

        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

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

        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

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
        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

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
        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

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
        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // stm fetchColumn gives no result
        $this->mutex->acquire('dummyLock');
    }

    public function testAcquireExisting()
    {
        $lockName = 'dummyLock';

        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

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

    public function testRelease()
    {
        $lockName = 'dummyLock';

        $this->mutex->expects($this->once())
            ->method('createConnection')
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

    public function testReleaseFailed()
    {
        $lockName = 'dummyLock';

        $this->mutex->expects($this->once())
            ->method('createConnection')
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

        $this->mutex->acquire($lockName);

        // Invalid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(0));

        $result = $this->mutex->release($lockName);

        $this->assertEquals(false, $result);
    }

    public function testReleaseNoLock()
    {
        $this->mutex->expects($this->never())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->never())
            ->method('prepare');

        // The stm should not be executed
        $this->stmtReleaseLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->release('dummyLock');

        $this->assertEquals(false, $result);
    }

    public function testMultiAcquireTwo()
    {
        $this->mutex->expects($this->exactly(2))
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // Valid lock
        $this->stmtGetLock->expects($this->exactly(2))
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result1 = $this->mutex->acquire('lock1');
        $this->assertEquals(true, $result1);

        $result2 = $this->mutex->acquire('lock2');
        $this->assertEquals(true, $result2);
    }

    public function testMultiAcquireTwoReleaseOne()
    {
        $this->mutex->expects($this->exactly(2))
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $this->pdo->expects($this->at(1))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $this->pdo->expects($this->at(2))
            ->method('prepare')
            ->will($this->returnValue($this->stmtReleaseLock));

        // Valid lock
        $this->stmtGetLock->expects($this->exactly(2))
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result1 = $this->mutex->acquire('lock1');
        $this->assertEquals(true, $result1);

        $result2 = $this->mutex->acquire('lock2');
        $this->assertEquals(true, $result2);

        // Valid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('execute')
            ->with(array('lock1'));

        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->release('lock1');

        $this->assertEquals(true, $result);

        // Verify lock2
        $this->stmtGetLock->expects($this->never())
            ->method('execute');

        $result = $this->mutex->acquire('lock2');

        $this->assertEquals(true, $result);
    }

    public function testConnectionReuse()
    {
        $lockName = 'dummyLock';

        $this->mutex->expects($this->once())
            ->method('createConnection')
            ->will($this->returnValue($this->pdo));

        $this->pdo->expects($this->at(0))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));
        $this->pdo->expects($this->at(1))
            ->method('prepare')
            ->will($this->returnValue($this->stmtReleaseLock));
        $this->pdo->expects($this->at(2))
            ->method('prepare')
            ->will($this->returnValue($this->stmtGetLock));

        // Valid lock
        $this->stmtGetLock->expects($this->exactly(2))
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        // Valid unlock
        $this->stmtReleaseLock->expects($this->once())
            ->method('execute')
            ->with(array($lockName));
        $this->stmtReleaseLock->expects($this->once())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        $result = $this->mutex->acquire($lockName);
        $this->assertEquals(true, $result);

        $result = $this->mutex->release($lockName);
        $this->assertEquals(true, $result);

        $result = $this->mutex->acquire($lockName);
        $this->assertEquals(true, $result);
    }
}
