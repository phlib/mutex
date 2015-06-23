<?php

namespace Phlib\Mutex;

/**
 * Mutex helper functions
 *
 * @package Phlib\Mutex
 */
class Helper
{
    /**
     * Tries to get a resource (e.g. a row from the database) using the getClosure, and if it does not
     * exist, calls the createClosure to create the resource and return it
     *
     * The getClosure should return the resource, or throw a NotFoundException
     * The createClosure should create the resource and return the created resource
     *
     * @param MutexInterface $mutex
     * @param \Closure $getClosure
     * @param \Closure $createClosure
     * @param int $wait Number of seconds to wait for lock
     * @return mixed Value from get or create closures
     */
    public static function getOrCreate(MutexInterface $mutex, \Closure $getClosure, \Closure $createClosure, $wait = 0)
    {
        try {
            $value = $getClosure();
        } catch (NotFoundException $e) {

            if ($mutex->lock($wait) === false) {
                throw new \RuntimeException('Unable to acquire lock on mutex');
            }

            try {
                $value = $getClosure();
            } catch (NotFoundException $e) {
                $value = $createClosure();
            }

            if ($mutex->unlock() === false) {
                throw new \RuntimeException('Unable to release lock on mutex');
            }
        }

        return $value;
    }
}
