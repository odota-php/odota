Expect
======

Programmed dialogue with interactive programs. A very basic alternative to the
[`expect` command-line tool][man-expect] (Don Libes, NIST) and the
[`expect` PHP extension][php-expect] written in user-land PHP. Its intended
usage is controlling interactive programs in system tests.

[man-expect]: http://linux.die.net/man/1/expect
[php-expect]: http://php.net/manual/en/book.expect.php

| Branch  | Build status |
|---------|--------------|
| develop | [![Travis](https://travis-ci.org/expectphp/expect.svg?branch=develop)](https://travis-ci.org/expectphp/expect) |

## Installation

```shell-session
$ composer require --dev expectphp/expect
```

## API

Examples explain it all.

```php
use function Expect\Expect\program;

// Programmed dialogue with an interactive program.
program('echo -n " > "; read name; sleep 2; echo "Hello, $name!"')
    ->expect(' > ')
    ->sendln('Bob')
    ->timeoutAfter(3)
    ->expect('Hello, Bob!')
    ->success();

// Expectation time-outs default to 100ms, but can be adjusted.
program('sleep 2; echo OK')
    ->timeoutAfter(1)
    ->expect('OK');
// Expect\Expect\ExpectationTimedOutException

// Expect programs to fail.
program('test -e non-existent-file')
    ->failure();
```

## Platform support

Expect has been written on and tested on Ubuntu systems. While it ought to
work on all Linux systems and Mac OS, this is not tested. Windows is not
supported, because the use of `stream_select()` on file descriptors returned by
`proc_open()` [will fail][php-stream-select] and return `false` under Windows.

[php-stream-select]: http://php.net/manual/en/function.stream-select.php
