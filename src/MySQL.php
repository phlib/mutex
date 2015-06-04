<?php

namespace Phlib\Mutex;

use Phlib\Config;

class MySQL implements MutexInterface
{
    /**
     * @var array
     */
    protected $dbConfig;

    protected $stmtGetLock;
    protected $stmtReleaseLock;

    /**
     * @var \PDO[]
     */
    protected $locks = [];

    /**
     * Constructor
     *
     * @param array $dbConfig {
     *     @var string $host     Required.
     *     @var int    $port     Optional. Default 3306.
     *     @var string $username Optional. Default empty.
     *     @var string $password Optional. Default empty.
     *     @var string $dbname   Optional.
     *     @var int    $timeout  Optional. Connection timeout in seconds. Default 2.
     * }
     */
    public function __construct(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
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
        if (isset($this->locks[$name])) {
            return true;
        }

        $pdo = $this->getConnection();

        $stmt = $pdo->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute(array($name, $timeout));
        $result = $stmt->fetchColumn();

        if (is_numeric($result)) {
            if ($result == 1) {
                $this->locks[$name] = $pdo;
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
        if (isset($this->locks[$name]) && $this->locks[$name] instanceof \PDO) {
            $pdo = $this->locks[$name];
            $this->locks[$name] = null;

            $stmt = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $stmt->execute(array($name));

            return ($stmt->fetchColumn() == 1);
        }

        return false;
    }

    /**
     * Get new connection
     *
     * @return \PDO
     */
    protected function getConnection()
    {
        if (!isset($this->dbConfig['host'])) {
            throw new \InvalidArgumentException('Missing host config param');
        }

        $dsn = "mysql:host={$this->dbConfig['host']}";

        if (isset($this->dbConfig['port'])) {
            $dsn .= ";port={$this->dbConfig['port']}";
        }

        if (isset($this->dbConfig['dbname'])) {
            $dsn .= ";dbname={$this->dbConfig['dbname']}";
        }

        $timeout = filter_var(
            Config::get($this->dbConfig, 'timeout'),
            FILTER_VALIDATE_INT,
            array(
                'options' => array(
                    'default'   => 2,
                    'min_range' => 0,
                    'max_range' => 120
                )
            )
        );

        $options = array(
            \PDO::ATTR_TIMEOUT            => $timeout,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        );

        $connection = new \PDO(
            $dsn,
            Config::get($this->dbConfig, 'username', ''),
            Config::get($this->dbConfig, 'password', ''),
            $options
        );

        return $connection;
    }
}
