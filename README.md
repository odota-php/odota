Expecto
=======

Programmed dialogue with interactive programs.

| Branch  | Build status |
|---------|--------------|
| develop | [![Travis](https://travis-ci.org/rjkip/expecto.svg?branch=develop)](https://travis-ci.org/rjkip/expecto) |

## Installation

```shell-session
$ composer require [--dev] rjkip/expecto
```

## Usages

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
