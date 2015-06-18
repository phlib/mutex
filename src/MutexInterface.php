<?php

namespace Phlib\Mutex;

/**
 * Mutex interface
 *
 * @package Phlib\Mutex
 */
interface MutexInterface
{

    /**
     * Lock
     *
     * @param int $wait Number of seconds to wait for lock
     * @return bool
     */
    public function lock($wait = 0);

    /**
     * Unlock
     *
     * @return bool
     */
    public function unlock();
}
