# phlib/mutex

[![Build Status](https://img.shields.io/travis/phlib/mutex/master.svg?style=flat-square)](https://travis-ci.org/phlib/mutex)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/mutex.svg)](https://codecov.io/gh/phlib/mutex)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/mutex.svg?style=flat-square)](https://packagist.org/packages/phlib/mutex)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/mutex.svg?style=flat-square)](https://packagist.org/packages/phlib/mutex)
![Licence](https://img.shields.io/github/license/phlib/mutex.svg)

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

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
