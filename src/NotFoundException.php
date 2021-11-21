<?php

declare(strict_types=1);

namespace Phlib\Mutex;

/**
 * Exception used to represent a value not found
 *
 * @package Phlib\Mutex
 * @see Phlib\Mutex\Mutex::getorCreate()
 * @extends \RuntimeException
 */
class NotFoundException extends \RuntimeException
{
    // void
}
