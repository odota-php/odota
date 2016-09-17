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

 * [Installation](#installation)
 * [API](#api)
 * [Limitations](#limitations)
 * [Platform support](#platform-support)
 * [Why...](#why)
    * [... not test against the PHP CLI application in user-land?](#-not-test-against-the-php-cli-application-in-user-land)
    * [... create a PHP library when there are tools like `expect` and `empty`?](#-create-a-php-library-when-there-are-tools-like-expect-and-empty)

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
    ->expectExitCode(0);

// Expectation time-outs default to 100ms, but can be adjusted.
program('sleep 2; echo OK')
    ->timeoutAfter(1)
    ->expect('OK');
// Expect\Expect\ExpectationTimedOutException

// Expect programs to fail.
program('test -e non-existent-file')
    ->expectExitCode(1);

// Expect output on standard error
program('echo LOG >&2')
    ->expectError('LOG');
```

## Limitations

Currently, the communication to the system-under-test happens via pipes.
Programs may determine that their input doesn't come from a terminal—or
pseudo-terminal for that matter—and disable interactivity. Other implementations
come to mind, and these may be implemented as different drivers in the future:

 * `proc_open()` with pty descriptors instead of pipes. Pseudo-terminal support
   in PHP, however, is undocumented and [not supported by Travis][travis-pty]
   at the time of writing (Sep 2016).
 * Writing a script to STDIN of the [`expect`][man-expect] binary.
 * Using the [`empty`][man-empty] command.

When testing Symfony CLI applications, set the
[`SHELL_INTERACTIVE`][pr-shell-interactive] environment variable to true to
force interactivity.

[travis-pty]: https://travis-ci.org/expectphp/expect/jobs/147116695#L264
[man-expect]: http://linux.die.net/man/1/expect
[man-empty]: http://manpages.ubuntu.com/manpages/trusty/man1/empty.1.html
[pr-shell-interactive]: https://github.com/symfony/symfony/pull/14102

## Platform support

Expect has been written on and tested on Ubuntu systems. While it ought to
work on all Linux systems and Mac OS, this is not tested. Windows is not
supported, because the use of `stream_select()` on file descriptors returned by
`proc_open()` [will fail][php-stream-select] and return `false` under Windows.

[php-stream-select]: http://php.net/manual/en/function.stream-select.php

## Why...

### ... not test against the PHP CLI application in user-land?

A simpler way of testing PHP CLI applications would be to boot the application
in the same execution context as the test, like Symfony
[helps you do][sf-cli-testing]. There are a couple of reasons for testing
against the final executable. Let's start with a remark by Nat Price:

> System tests exercise the entire system end-to-end, driving the system through
> its published remote interfaces and user interface. They also exercise the
> packaging, deployment and startup of the system. ([source][nat-pryce-system],
> as of September 17, 2016)

So testing against the final executable (the “production” executable), you get
closer to the “production” state of your application, namely as a PHP script
called from the shell. The difference is even greater when you package your
application as a [Phar][php-phar].

[nat-pryce-system]: http://www.natpryce.com/articles/000772.html
[php-phar]: http://php.net/manual/en/book.phar.php

### ... create a PHP library when there are tools like `expect` and `empty`?

 * To enable testing with a more familiar language, PHP. `expect` uses Tcl.
 * To enable testing with your favourite PHP testing framework.
 * To provide a testing API, rather than just interactive dialogue.
 * To allow testing your system-under-test's side-effects, like file system
   changes, in the same test context.
