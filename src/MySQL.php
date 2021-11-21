<?php

declare(strict_types=1);

namespace Phlib\Mutex;

/**
 * MySQL Mutex
 *
 * @package Phlib\Mutex
 */
class MySQL implements MutexInterface
{
    private array $dbConfig;

    private string $name;

    private \PDO $connection;

    private \PDOStatement $stmtGetLock;

    private \PDOStatement $stmtReleaseLock;

    private bool $isLocked = false;

    /**
     * @param array $dbConfig {
     *     @var string $host     Required.
     *     @var int    $port     Optional. Default 3306.
     *     @var string $username Optional. Default empty.
     *     @var string $password Optional. Default empty.
     *     @var int    $timeout  Optional. Connection timeout in seconds. Default 2.
     * }
     */
    public function __construct(string $name, array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
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
            $pdo = $this->getConnection();
            $this->stmtGetLock = $pdo->prepare('SELECT GET_LOCK(?, ?)');
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
                $pdo = $this->getConnection();
                $this->stmtReleaseLock = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            }

            $this->isLocked = false;
            $this->stmtReleaseLock->execute([$this->name]);

            return ((int)$this->stmtReleaseLock->fetchColumn() === 1);
        }

        return false;
    }

    /**
     * Get connection, create if required
     *
     * Visibility `protected` so PDO dependency can be mocked in unit tests.
     * @todo Refactor for proper dependency injection.
     */
    protected function getConnection(): \PDO
    {
        if (!isset($this->connection)) {
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
                [
                    'options' => [
                        'default' => 2,
                        'min_range' => 0,
                        'max_range' => 120,
                    ],
                ]
            );

            $options = [
                \PDO::ATTR_TIMEOUT => $timeout,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ];

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
