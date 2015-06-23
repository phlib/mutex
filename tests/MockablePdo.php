<?php

namespace Phlib\Mutex\Test;

/**
 * Mockable PDO to disable constructor and avoid serialization issue with PHPUnit
 *
 * @package Phlib\Mutex\Test
 */
class MockablePdo extends \PDO
{
    /**
     * Disable original constructor
     */
    public function __construct()
    {
        // void
    }
}
