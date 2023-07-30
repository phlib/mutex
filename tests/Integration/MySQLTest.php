<?php

declare(strict_types=1);

namespace Phlib\Mutex\Test\Integration;

use Phlib\Db\Adapter;
use Phlib\Mutex\MySQL;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class MySQLTest extends TestCase
{
    private Adapter $dbAdapter;

    protected function setUp(): void
    {
        if ((bool)getenv('INTEGRATION_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        parent::setUp();

        $this->dbAdapter = new Adapter([
            'host' => getenv('INTEGRATION_DB_HOST'),
            'port' => getenv('INTEGRATION_DB_PORT'),
            'username' => getenv('INTEGRATION_DB_USERNAME'),
            'password' => getenv('INTEGRATION_DB_PASSWORD'),
        ]);
    }

    public function testLock(): void
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbAdapter);

        $lockResult = $mutex->lock();
        static::assertTrue($lockResult);

        $unlockResult = $mutex->unlock();
        static::assertTrue($unlockResult);
    }

    public function testLockRepeat(): void
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbAdapter);

        $lockAResult = $mutex->lock();
        static::assertTrue($lockAResult);

        // Second attempt to lock returns true early
        $lockBResult = $mutex->lock();
        static::assertTrue($lockBResult);

        $unlockAResult = $mutex->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockBlocked(): void
    {
        $name = sha1(uniqid());

        $mutexA = new MySQL($name, $this->dbAdapter);

        // Clone Adapter to get separate connection
        $dbAdapterB = clone $this->dbAdapter;
        $mutexB = new MySQL($name, $dbAdapterB);

        $lockAResult = $mutexA->lock();
        static::assertTrue($lockAResult);

        // Second lock fails as first already has lock
        $lockBResult = $mutexB->lock();
        static::assertFalse($lockBResult);

        $unlockAResult = $mutexA->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockTimeout(): void
    {
        $name = sha1(uniqid());

        $mutexA = new MySQL($name, $this->dbAdapter);

        // Clone Adapter to get separate connection
        $dbAdapterB = clone $this->dbAdapter;
        $mutexB = new MySQL($name, $dbAdapterB);

        $lockAResult = $mutexA->lock();
        static::assertTrue($lockAResult);

        // Second lock should fail after timeout
        // Use microtime to check it really is a 1s wait, not just tripping over between int values which could be ~0s
        $lockTimeout = 1;
        $startTime = microtime(true);
        $lockBResult = $mutexB->lock($lockTimeout);
        $endTime = microtime(true);
        static::assertFalse($lockBResult);
        static::assertEqualsWithDelta($lockTimeout, $endTime - $startTime, 0.01);

        $unlockAResult = $mutexA->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockError(): void
    {
        $this->expectException(\PDOException::class);
        $mysql57 = 'Incorrect user-level lock name';
        $mysql8 = "4163 User-level lock name '\w+' should not exceed 64";
        $this->expectExceptionMessageMatches("({$mysql57}|{$mysql8})");

        // MySQL 5.7 enforces a maximum length on lock names of 64 characters
        $name = str_repeat(sha1(uniqid()), 3);

        $mutex = new MySQL($name, $this->dbAdapter);

        $lockResult = $mutex->lock();
        static::assertTrue($lockResult);
    }

    public function testUnlockNoLock(): void
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbAdapter);

        $unlockResult = $mutex->unlock();
        static::assertFalse($unlockResult);
    }
}
