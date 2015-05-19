<?php

namespace Phlib\Mutex;

interface MutexInterface
{

    /**
     * Acquire
     *
     * @param string $name
     * @param int $timeout Number of seconds
     * @return bool
     */
    public function acquire($name, $timeout = 0);

    /**
     * Release
     *
     * @param string $name
     * @return bool
     */
    public function release($name);
}
