<?php

namespace Phlib\Mutex;

class MySQL implements MutexInterface
{

    protected $stmtGetLock;
    protected $stmtReleaseLock;
    protected $lock = null;

    /**
     * Constructor
     *
     * @param \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->stmtGetLock     = $db->prepare('SELECT GET_LOCK(?, ?)');
        $this->stmtReleaseLock = $db->prepare('SELECT RELEASE_LOCK(?)');
    }

    /**
     * Acquire
     *
     * @param string $name
     * @param int $timeout
     * @return boolean
     * @throws \RuntimeException
     */
    public function acquire($name, $timeout = 0)
    {
        if ($this->lock == $name) {
            return true;
        }

        $this->lock = null;

        $stmt = $this->stmtGetLock;
        $stmt->execute(array($name, $timeout));
        $result = $stmt->fetchColumn();

        if (is_numeric($result)) {
            if ($result == 1) {
                $this->lock = $name;
                return true;
            } else if ($result == 0) {
                return false;
            }
        }

        throw new \RuntimeException("Failure on mutex '$name'");
    }

    /**
     * Release
     *
     * @param string $name
     * @return boolean
     */
    public function release($name)
    {
        if ($this->lock == $name) {
            $this->lock = null;
            $this->stmtReleaseLock->execute(array($name));
            return ($this->stmtReleaseLock->fetchColumn() == 1);
        }

        return false;
    }
}
