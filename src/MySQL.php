<?php

namespace Phlib\Mutex;

/**
 * MySQL Mutex
 *
 * @package Phlib\Mutex
 */
class MySQL implements MutexInterface
{
    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var \PDOStatement
     */
    protected $stmtGetLock;

    /**
     * @var \PDOStatement
     */
    protected $stmtReleaseLock;

    /**
     * @var bool
     */
    protected $isLocked = false;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $dbConfig {
     *     @var string $host     Required.
     *     @var int    $port     Optional. Default 3306.
     *     @var string $username Optional. Default empty.
     *     @var string $password Optional. Default empty.
     *     @var int    $timeout  Optional. Connection timeout in seconds. Default 2.
     * }
     */
    public function __construct($name, array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
        $this->name = $name;
    }

    /**
     * Lock
     *
     * @param int $wait Number of seconds to wait for lock
     * @return boolean
     * @throws \RuntimeException
     */
    public function lock($wait = 0)
    {
        if ($this->isLocked) {
            return true;
        }

        if (!$this->stmtGetLock instanceof \PDOStatement) {
            $pdo = $this->getConnection();
            $this->stmtGetLock = $pdo->prepare('SELECT GET_LOCK(?, ?)');
        }

        $this->stmtGetLock->execute(array($this->name, $wait));
        $result = $this->stmtGetLock->fetchColumn();

        if (is_numeric($result)) {
            if ($result == 1) {
                $this->isLocked = true;
                return true;
            } else if ($result == 0) {
                return false;
            }
        }

        throw new \RuntimeException("Failure on mutex '{$this->name}'");
    }

    /**
     * Unlock
     *
     * @return boolean
     */
    public function unlock()
    {
        if ($this->isLocked) {
            if (!$this->stmtReleaseLock instanceof \PDOStatement) {
                $pdo = $this->getConnection();
                $this->stmtReleaseLock = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            }

            $this->isLocked = false;
            $this->stmtReleaseLock->execute(array($this->name));

            return ($this->stmtReleaseLock->fetchColumn() == 1);
        }

        return false;
    }

    /**
     * Get connection, create if required
     *
     * @return \PDO
     */
    protected function getConnection()
    {
        if (!$this->connection instanceof \PDO) {
            if (!isset($this->dbConfig['host'])) {
                throw new \InvalidArgumentException('Missing host config param');
            }

            $dsn = "mysql:host={$this->dbConfig['host']}";

            if (isset($this->dbConfig['port'])) {
                $dsn .= ";port={$this->dbConfig['port']}";
            }

            $timeout = filter_var(
                \Phlib\Config\get($this->dbConfig, 'timeout'),
                FILTER_VALIDATE_INT,
                array(
                    'options' => array(
                        'default' => 2,
                        'min_range' => 0,
                        'max_range' => 120
                    )
                )
            );

            $options = array(
                \PDO::ATTR_TIMEOUT => $timeout,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            );

            $this->connection = new \PDO(
                $dsn,
                \Phlib\Config\get($this->dbConfig, 'username', ''),
                \Phlib\Config\get($this->dbConfig, 'password', ''),
                $options
            );
        }

        return $this->connection;
    }
}
