# phlib/mutex

[![Latest Version](https://img.shields.io/github/release/phlib/mutex.svg?style=flat-square)](https://github.com/phlib/mutex/releases)
[![Build Status](https://img.shields.io/travis/phlib/mutex/master.svg?style=flat-square)](https://travis-ci.org/phlib/mutex)
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
$mutex = new \Phlib\Mutex\MySQL([
    'host'     => '127.0.0.1',
    'username' => 'my-user',
    'password' => 'my-pass',
    'dbname'   => 'mydb'
]);
$mutex->acquire('my-lock');
// Do some data manipulation while locked
$mutex->release('my-lock');
```

### Helpers

**Get-Or-Create** provides a simple way to attempt retrieval of a resource,
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
