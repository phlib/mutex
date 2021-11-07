<?php

declare(strict_types=1);

namespace Phlib\Mutex\Test\Integration;

use Phlib\Mutex\MySQL;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class MySQLTest extends TestCase
{
    /**
     * @var array
     */
    private $dbConfig;

    protected function setUp()
    {
        if ((bool)getenv('INTEGRATION_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        parent::setUp();

        $this->dbConfig = [
            'host' => getenv('INTEGRATION_DB_HOST'),
            'port' => getenv('INTEGRATION_DB_PORT'),
            'username' => getenv('INTEGRATION_DB_USERNAME'),
            'password' => getenv('INTEGRATION_DB_PASSWORD'),
        ];
    }

    public function testLock()
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbConfig);

        $lockResult = $mutex->lock();
        static::assertTrue($lockResult);

        $unlockResult = $mutex->unlock();
        static::assertTrue($unlockResult);
    }

    public function testLockRepeat()
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbConfig);

        $lockAResult = $mutex->lock();
        static::assertTrue($lockAResult);

        // Second attempt to lock returns true early
        $lockBResult = $mutex->lock();
        static::assertTrue($lockBResult);

        $unlockAResult = $mutex->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockBlocked()
    {
        $name = sha1(uniqid());

        $mutexA = new MySQL($name, $this->dbConfig);
        $mutexB = new MySQL($name, $this->dbConfig);

        $lockAResult = $mutexA->lock();
        static::assertTrue($lockAResult);

        // Second lock fails as first already has lock
        $lockBResult = $mutexB->lock();
        static::assertFalse($lockBResult);

        $unlockAResult = $mutexA->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockTimeout()
    {
        $name = sha1(uniqid());

        $mutexA = new MySQL($name, $this->dbConfig);
        $mutexB = new MySQL($name, $this->dbConfig);

        $lockAResult = $mutexA->lock();
        static::assertTrue($lockAResult);

        // Second lock should fail after timeout
        // Use microtime to check it really is a 1s wait, not just tripping over between int values which could be ~0s
        $lockTimeout = 1;
        $startTime = microtime(true);
        $lockBResult = $mutexB->lock($lockTimeout);
        $endTime = microtime(true);
        static::assertFalse($lockBResult);
        static::assertEquals($lockTimeout, $endTime - $startTime, '', 0.01);

        $unlockAResult = $mutexA->unlock();
        static::assertTrue($unlockAResult);
    }

    public function testLockError()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Incorrect user-level lock name');

        // MySQL 5.7 enforces a maximum length on lock names of 64 characters
        $name = str_repeat(sha1(uniqid()), 3);

        $mutex = new MySQL($name, $this->dbConfig);

        $lockResult = $mutex->lock();
        static::assertTrue($lockResult);
    }

    public function testUnlockNoLock()
    {
        $name = sha1(uniqid());

        $mutex = new MySQL($name, $this->dbConfig);

        $unlockResult = $mutex->unlock();
        static::assertFalse($unlockResult);
    }
}
