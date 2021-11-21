<?php

declare(strict_types=1);

namespace Phlib\Mutex\Test;

use Phlib\Mutex\MySQL;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test MySQL
 *
 * @package Phlib\Mutex
 */
class MySQLTest extends TestCase
{
    private const LOCK_NAME = 'dummyLock';

    /**
     * @var MySQL|MockObject
     */
    private $mutex;

    /**
     * @var \PDO|MockObject
     */
    private $pdo;

    /**
     * @var \PDOStatement|MockObject
     */
    private $stmtGetLock;

    /**
     * @var \PDOStatement|MockObject
     */
    private $stmtReleaseLock;

    protected function setUp(): void
    {
        // Mock PDO classes
        $this->pdo = $this->createMock(\PDO::class);
        $this->stmtGetLock = $this->createMock(\PDOStatement::class);
        $this->stmtReleaseLock = $this->createMock(\PDOStatement::class);

        $this->mutex = $this->getMockBuilder(MySQL::class)
            ->setConstructorArgs([self::LOCK_NAME, []])
            ->setMethods(['getConnection'])
            ->getMock();
    }

    public function testLock(): void
    {
        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        $this->stmtGetLock->expects(static::once())
            ->method('execute')
            ->with([self::LOCK_NAME, 0]);

        // Valid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $result = $this->mutex->lock();

        static::assertTrue($result);
    }

    public function testLockTimeout(): void
    {
        $lockTimeout = 30;

        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        $this->stmtGetLock->expects(static::once())
            ->method('execute')
            ->with([self::LOCK_NAME, $lockTimeout]);

        // Valid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $result = $this->mutex->lock($lockTimeout);

        static::assertTrue($result);
    }

    public function testLockFailed(): void
    {
        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        // Invalid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(0);

        $result = $this->mutex->lock();

        static::assertFalse($result);
    }

    public function testLockInvalidResult(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failure on mutex');

        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        // Invalid lock result
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(2);

        $this->mutex->lock();
    }

    public function testLockError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failure on mutex');

        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        // stmt fetchColumn gives no result
        $this->mutex->lock();
    }

    public function testLockExisting(): void
    {
        $this->mutex->expects(static::once())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);

        // Valid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->mutex->lock();

        // Now it's locked, another acquire should not execute the stm
        $this->stmtGetLock->expects(static::never())
            ->method('execute');

        $result = $this->mutex->lock();

        static::assertTrue($result);
    }

    public function testUnlock(): void
    {
        $this->mutex->expects(static::exactly(2))
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);
        $this->pdo->expects(static::at(1))
            ->method('prepare')
            ->willReturn($this->stmtReleaseLock);

        // Valid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->mutex->lock();

        // Valid unlock
        $this->stmtReleaseLock->expects(static::once())
            ->method('execute')
            ->with([self::LOCK_NAME]);

        $this->stmtReleaseLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $result = $this->mutex->unlock();

        static::assertTrue($result);
    }

    public function testUnlockFailed(): void
    {
        $this->mutex->expects(static::exactly(2))
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::at(0))
            ->method('prepare')
            ->willReturn($this->stmtGetLock);
        $this->pdo->expects(static::at(1))
            ->method('prepare')
            ->willReturn($this->stmtReleaseLock);

        // Valid lock
        $this->stmtGetLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->mutex->lock();

        // Invalid unlock
        $this->stmtReleaseLock->expects(static::once())
            ->method('fetchColumn')
            ->willReturn(0);

        $result = $this->mutex->unlock();

        static::assertFalse($result);
    }

    public function testUnlockNoLock(): void
    {
        $this->mutex->expects(static::never())
            ->method('getConnection')
            ->willReturn($this->pdo);

        $this->pdo->expects(static::never())
            ->method('prepare');

        // The stmt should not be executed
        $this->stmtReleaseLock->expects(static::never())
            ->method('execute');

        $result = $this->mutex->unlock();

        static::assertFalse($result);
    }
}
