Expecto
=======

Programmed dialogue with interactive programs. A very basic alternative to the
[`expect` command-line tool][man-expect] (Don Libes, NIST) and the
[`expect` PHP extension][php-expect] written in user-land PHP. Its intended
usage is controlling interactive programs in system tests.

[man-expect]: http://linux.die.net/man/1/expect
[php-expect]: http://php.net/manual/en/book.expect.php

| Branch  | Build status |
|---------|--------------|
| develop | [![Travis](https://travis-ci.org/rjkip/expecto.svg?branch=develop)](https://travis-ci.org/rjkip/expecto) |

## Installation

```shell-session
$ composer require [--dev] rjkip/expecto
```

## API

Examples explain it all.

```php
use function Expecto\Expecto\program;

// Programmed dialogue with an interactive program.
program('echo -n " > "; read name; sleep 2; echo "Hello, $name!"')
    ->expect(' > ')
    ->sendln('Bob')
    ->timeoutAfter(3)
    ->expect('Hello, Bob!');

// Expectation time-outs default to 100ms, but can be adjusted.
program('sleep 2; echo OK')
    ->timeoutAfter(1)
    ->expect('OK');
// Expecto\Expecto\ExpectationTimedOutException
```

## Platform support

Expecto has been written on and tested on Ubuntu systems. While it ought to
work on all Linux systems, and also on Windows and Mac OS, this is not tested.
