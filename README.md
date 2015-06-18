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

```php
$mutex = new \Phlib\Mutex\MySQL('my-lock', [
    'host'     => '127.0.0.1',
    'username' => 'my-user',
    'password' => 'my-pass',
    'dbname'   => 'mydb'
]);
$mutex->lock();
// Do some data manipulation while locked
$mutex->unlock();
```
