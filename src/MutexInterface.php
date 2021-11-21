<?php

declare(strict_types=1);

namespace Phlib\Mutex;

/**
 * Mutex interface
 *
 * @package Phlib\Mutex
 */
interface MutexInterface
{
    /**
     * @param int $wait Number of seconds to wait for lock
     */
    public function lock(int $wait = 0): bool;

    public function unlock(): bool;
}
