<?php

declare(strict_types=1);

namespace Phlib\Mutex;

use Phlib\Db\Adapter;

/**
 * MySQL Mutex
 *
 * @package Phlib\Mutex
 */
class MySQL implements MutexInterface
{
    private string $name;

    private Adapter $dbAdapter;

    private \PDOStatement $stmtGetLock;

    private \PDOStatement $stmtReleaseLock;

    private bool $isLocked = false;

    public function __construct(string $name, Adapter $dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        $this->name = $name;
    }

    /**
     * @param int $wait Number of seconds to wait for lock
     */
    public function lock(int $wait = 0): bool
    {
        if ($this->isLocked) {
            return true;
        }

        if (!isset($this->stmtGetLock)) {
            $this->stmtGetLock = $this->dbAdapter->prepare('SELECT GET_LOCK(?, ?)');
        }

        $this->stmtGetLock->execute([$this->name, $wait]);
        $result = $this->stmtGetLock->fetchColumn();

        if (is_numeric($result)) {
            if ((int)$result === 1) {
                $this->isLocked = true;
                return true;
            } elseif ((int)$result === 0) {
                return false;
            }
        }

        throw new \RuntimeException("Failure on mutex '{$this->name}'");
    }

    public function unlock(): bool
    {
        if ($this->isLocked) {
            if (!isset($this->stmtReleaseLock)) {
                $this->stmtReleaseLock = $this->dbAdapter->prepare('SELECT RELEASE_LOCK(?)');
            }

            $this->isLocked = false;
            $this->stmtReleaseLock->execute([$this->name]);

            return ((int)$this->stmtReleaseLock->fetchColumn() === 1);
        }

        return false;
    }
}
