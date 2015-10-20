# phlib/mutex

[![Build Status](https://img.shields.io/travis/phlib/mutex/master.svg?style=flat-square)](https://travis-ci.org/phlib/mutex)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/mutex.svg?style=flat-square)](https://packagist.org/packages/phlib/mutex)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/mutex.svg?style=flat-square)](https://packagist.org/packages/phlib/mutex)

PHP mutex handling in different ways

## Install

Via Composer

``` bash
$ composer require phlib/mutex
```

## Usage

### MySQL

```php
$mutex = new \Phlib\Mutex\MySQL('my-lock', [
    'host'     => '127.0.0.1',
    'username' => 'my-user',
    'password' => 'my-pass'
]);
if ($mutex->lock()) {
    // Do some data manipulation while locked
    $mutex->unlock();
}
```

### Helpers

**Get-Or-Create** provides a simple way to attempt retrieval of a value,
or create it using a mutex if it doesn't already exist

```php
$getClosure = function() {
    // attempt to get a value, eg. from DB, cache, etc.
    if (!$value) {
        throw new \Phlib\Mutex\NotFoundException();
    }
    return $value;
};

$createClosure = function() {
    // attempt to create a value and write eg. to DB, cache, etc.
    return $value;
};

$value = \Phlib\Mutex\Helper::getOrCreate($mutex, $getClosure, $createClosure);
```
