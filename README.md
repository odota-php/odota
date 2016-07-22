Expecto (rjkip/expecto)
=======================

Programmed dialogue with interactive programs.

| Branch  | Build status |
|---------|--------------|
| develop | [![Travis](https://travis-ci.org/rjkip/expecto.svg?branch=develop)](https://travis-ci.org/rjkip/expecto) |

## Example

```php
use function Expecto\Expecto\program;

program('echo -n " > "; read name; echo "Hello, $name!"')
    ->expect(' > ')
    ->sendln('Bob')
    ->expect('Hello, Bob!');
```
