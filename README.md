Odota ![stability-experimental](https://cloud.githubusercontent.com/assets/1734555/18616629/a740d892-7dbf-11e6-8718-64afa66fac0d.png) [![Travis build status](https://travis-ci.org/odota-php/odota.svg?branch=develop)](https://travis-ci.org/odota-php/odota)
===============================================================================================================================================================================================================================================================

Programmed dialogue with interactive programs for system testing using PHP 5.6,
PHP 7.x and HHVM. Written in PHP, it is easily integrated in your testing
framework of choice.

--------------------------------------------------------------------------------

 * [Installation](#installation)
 * [API](#api)
 * [Limitations](#limitations)
 * [Caveats](#caveats)
 * [Platform support](#platform-support)
 * [Why...](#why)
    * [... not test against the PHP CLI application in user-land?](#-not-test-against-the-php-cli-application-in-user-land)
    * [... create a PHP library when there are tools like `expect` and `empty`?](#-create-a-php-library-when-there-are-tools-like-expect-and-empty)

--------------------------------------------------------------------------------

## Installation

```shell-session
$ composer require --dev odota/odota
```

## API

Examples explain it all.

```php
use function Odota\Odota\spawn;

// Programmed dialogue with an interactive program.
spawn('echo -n " > "; read name; sleep 2; echo "Hello, $name!"')
    ->expect(' > ')
    ->sendln('Bob')
    ->timeoutAfter(3)
    ->expect('Hello, Bob!')
    ->expectExitCode(0);

// Expectation time-outs default to 100ms, but can be adjusted.
spawn('sleep 2; echo OK')
    ->timeoutAfter(1)
    ->expect('OK');
// Odota\Odota\ExpectationTimedOutException

// Expect programs to fail.
spawn('test -e non-existent-file')
    ->expectExitCode(1);

// Expect output on standard error
spawn('echo LOG >&2')
    ->expectError('LOG');
```

## Limitations

Currently, the communication to the system-under-test happens via pipes.
Programs may determine that their input doesn't come from a terminal—or
pseudo-terminal for that matter—and disable interactivity. Other implementations
come to mind, and these may be implemented as different drivers in the future:

 * `proc_open()` with pty descriptors instead of pipes. Pseudo-terminal support
   in PHP, however, is undocumented and not supported by Travis at the time of
   writing (Sep 2016).
 * Writing a script to STDIN of the [`expect`][man-expect] binary.
 * Using the [`empty`][man-empty] command.

When testing Symfony CLI applications, set the
[`SHELL_INTERACTIVE`][pr-shell-interactive] environment variable to true to
force interactivity.

[man-expect]: http://linux.die.net/man/1/expect
[man-empty]: http://manpages.ubuntu.com/manpages/trusty/man1/empty.1.html
[pr-shell-interactive]: https://github.com/symfony/symfony/pull/14102

## Caveats

To pass the test caller's environment variables on to the system under test,
the ini setting `variables_order` must include `E` to fill the `$_ENV`
superglobal. Any easy fix is by including this ini setting on the command line
when calling your test framework:

```sh-session
$ php -d variables_order=EGPCS vendor/bin/phpunit
```

## Platform support

Odota is tested against PHP 5.6, 7.x and HHVM 3.6 on Ubuntu-like systems. It
should work on common Unixy systems, including Mac OS. Windows is not supported,
because `stream_select()` on file descriptors returned by `proc_open()`
[will fail][php-stream-select] under Windows.

[php-stream-select]: http://php.net/manual/en/function.stream-select.php "Documentation for the PHP function `stream_select()` (php.net)"

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

[nat-pryce-system]: http://www.natpryce.com/articles/000772.html "Nat Pryce on System Tests (natpryce.com)"
[php-phar]: http://php.net/manual/en/book.phar.php "Documentation about PHP Archives (php.net)"

### ... create a PHP library when there are tools like `expect` and `empty`?

 * To enable testing with a more familiar language, PHP. `expect` uses Tcl.
 * To enable testing with your favourite PHP testing framework.
 * To provide a testing API, rather than just interactive dialogue.
 * To allow testing your system-under-test's side-effects, like file system
   changes, in the same test context.
