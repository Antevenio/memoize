# antevenio/memoize
[![Latest Stable Version](https://poser.pugx.org/antevenio/memoize/v/stable)](https://packagist.org/packages/antevenio/memoize)
[![Total Downloads](https://poser.pugx.org/antevenio/memoize/downloads)](https://packagist.org/packages/antevenio/memoize)
[![License](https://poser.pugx.org/antevenio/memoize/license)](https://packagist.org/packages/antevenio/memoize)
[![Travis build](https://api.travis-ci.org/Antevenio/memoize.svg?branch=master)](https://travis-ci.org/Antevenio/memoize)
[![Coverage Status](https://coveralls.io/repos/github/Antevenio/memoize/badge.svg?branch=master)](https://coveralls.io/github/Antevenio/memoize?branch=master)
[![Maintainability](https://api.codeclimate.com/v1/badges/d1e9627d0179402a3d71/maintainability)](https://codeclimate.com/github/Antevenio/memoize/maintainability)

Yet another in memory function memoizing library. 

## Features:
* Can set a limit to the total memory consumption of the cache.
* Can place a TTL (time to live) per callable. 
(meaning that memoize will be returning the callable cached results until the TTL expires,
in which case it will call the function again and generate a new cached result)  
* Can override the default callable argument cache indexing with a custom one. 
(you can reuse a cached callable even passing different arguments if you want to do so)
* Caches thrown exceptions.

## Behaviour:
* When out of memory, it evicts the oldest (first cached) callable first.

## Requirements
The following versions of PHP are supported.

* PHP 5.6
* PHP 7.0
* PHP 7.1
* PHP 7.2
* PHP 7.3

## Installation
```shell script
composer require antevenio/memoize
```

## Usage
### Common code
```php
<?php
require_once('./vendor/autoload.php');

use Antevenio\Memoize\Memoizable;
use Antevenio\Memoize\Memoize;
use Antevenio\Memoize\Cache;

class Toolbox
{
    public function multiply($argument)
    {
        echo "Called with {$argument}\n";

        return $argument * 2;
    }
    
    public function throwException($argument)
    {
        echo "Called with {$argument}\n";

        throw new \Exception($argument);
    }
}

$toolbox = new Toolbox();
$memoize = new Memoize(new Cache());
```
### Basic
```php
for ($i = 0; $i < 10; $i++) {
    $result = $memoize->memoize(
        (new Memoizable([$toolbox, 'multiply'], [10]))->withTtl(5)
    );
    echo "Result: $result\n";
    sleep(1);
}
```
->>>
```
Called with 10
Result: 20
Result: 20
Result: 20
Result: 20
Result: 20
Called with 10
Result: 20
Result: 20
Result: 20
Result: 20
Result: 20
```
### Changing arguments
```php
for ($i = 0; $i < 10; $i++) {
    $result = $memoize->memoize(
        (new Memoizable([$toolbox, 'multiply'], [$i % 2]))->withTtl(5)
    );
    echo "Result: $result\n";
    sleep(1);
}
```
->>>
```
Called with 0
Result: 0
Called with 1
Result: 2
Result: 0
Result: 2
Result: 0
Result: 2
Called with 0
Result: 0
Called with 1
Result: 2
Result: 0
Result: 2
```
### Custom indexing
```php
for ($i = 0; $i < 10; $i++) {
    $result = $memoize->memoize(
        (new Memoizable([$toolbox, 'multiply'], [$i]))->withTtl(5)->withCustomIndex('myFixedIndex')
    );
    echo "Result: $result\n";
    sleep(1);
}
```
->>>
```
Called with 0
Result: 0
Result: 0
Result: 0
Result: 0
Result: 0
Called with 5
Result: 10
Result: 10
Result: 10
Result: 10
Result: 10
```
### Exceptions
```php
for ($i = 0; $i < 10; $i++) {
    $result = $memoize->memoize(
        (new Memoizable([$toolbox, 'throwException'], ['foo']))->withTtl(5)
    );
    echo "Result: $result\n";
    sleep(1);
}
``` 
->>>
```
Called with foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
Called with foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
Thrown exception foo
```
