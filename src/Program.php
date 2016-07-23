<?php

namespace Expect\Expect;

final class Program
{
    /**
     * The default timeout (100ms) after which expectations time out. You can adjust
     * this per program using {timeoutAfter()}.
     */
    const DEFAULT_TIMEOUT = 0.100;

    /** @var resource */
    private $handle;
    /** @var resource */
    private $stdout;
    /** @var resource */
    private $stderr;
    /** @var resource */
    private $stdin;
    /** @var string */
    private $bufferStdout;
    /** @var string */
    private $bufferStderr;
    /** @var float */
    private $timeout;

    /**
     * @param string        $command
     * @param string|null   $workingDirectory
     * @param string[]|null $environmentVariables
     * @return Program
     */
    public static function interactWith($command, $workingDirectory = null, array $environmentVariables = null)
    {
        $workingDirectory     = $workingDirectory ?: getcwd();
        $environmentVariables = $environmentVariables ?: [];

        assertNonBlankString(
            $workingDirectory,
            'Working directory ought to be a non-empty string, got "%s" of type "%s"'
        );
        assertArrayOfEnvironmentVariables($environmentVariables);

        $process = proc_open(
            $command,
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
            ],
            $pipes,
            $workingDirectory,
            $environmentVariables
        );
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        return new Program($process, $pipes[1], $pipes[2], $pipes[0]);
    }

    /**
     * @param resource $handle
     * @param resource $stdout
     * @param resource $stderr
     * @param resource $stdin
     */
    private function __construct($handle, $stdout, $stderr, $stdin)
    {
        $this->handle  = $handle;
        $this->stdout  = $stdout;
        $this->stderr  = $stderr;
        $this->stdin   = $stdin;
        $this->bufferStdout = '';
        $this->bufferStderr = '';
        $this->timeout = self::DEFAULT_TIMEOUT;
    }

    /**
     * @param float|int $seconds
     * @return $this
     */
    public function timeoutAfter($seconds)
    {
        assertFloaty($seconds, 'Expected time-out to be a float, got "%s" of type "%s"');

        $this->timeout = $seconds;

        return $this;
    }

    /**
     * @param string $expected
     * @return $this
     */
    public function expect($expected)
    {
        $this->expectFromStream('stdout', $expected);

        return $this;
    }

    /**
     * @param string $expected
     * @return static
     */
    public function expectError($expected)
    {
        $this->expectFromStream('stderr', $expected);

        return $this;
    }

    /**
     * @param string $stream
     * @param string $expected
     */
    private function expectFromStream($stream, $expected)
    {
        assertString($expected, 'Expected expected string to be a string, got "%s" of type "%s"');

        $start = microtime(true);

        if ($stream === 'stdout') {
            $buffer = &$this->bufferStdout;
        } else {
            $buffer = &$this->bufferStderr;
        }

        while (true) {
            $timeLeft = $this->timeout - (microtime(true) - $start);

            if ($timeLeft <= 0) {
                throw new ExpectationTimedOutException(
                    sprintf(
                        'Stream "%s" did not output expected string "%s" within %.3f seconds',
                        $stream,
                        $expected,
                        $this->timeout
                    ),
                    $buffer
                );
            }

            $read     = [$this->$stream];
            $write    = [];
            $except   = [];
            $readable = stream_select($read, $write, $except, 0, $timeLeft * 1000 * 1000);

            if ($readable === false) {
                throw RuntimeException::format('Stream select error: "%s"', error_get_last()['message']);
            } elseif ($readable === 0) {
                continue;
            }

            do {
                $bytes = fread($this->$stream, 4096);
                $buffer .= $bytes;
            } while ($bytes !== '');

            $position = strpos($buffer, $expected);
            if ($position === false) {
                continue;
            }

            $buffer = substr($buffer, strlen($position));

            return;
        }

        throw LogicException::format('Should be unreachable code');
    }

    /**
     * @param string $string
     * @return static
     */
    public function sendln($string)
    {
        assertString($string, 'Expected string to send to be a string, got "%s" of type "%s"');
        fwrite($this->stdin, $string . PHP_EOL);

        return $this;
    }

    /**
     * @param string $string
     * @return static
     */
    public function send($string)
    {
        assertString($string, 'Expected string to send to be a string, got "%s" of type "%s"');
        fwrite($this->stdin, $string);

        return $this;
    }

    /**
     * @throws RuntimeException When the program's exit code is not 0
     * @throws ExpectationTimedOutException
     */
    public function success()
    {
        $exitCode = $this->wait();

        if ($exitCode !== 0) {
            throw RuntimeException::format('Expected program to exit successfully, got exit code %d', $exitCode);
        }
    }

    /**
     * @param int|null $expectedExitCode
     * @throws RuntimeException When the program's exit code is not 0
     * @throws ExpectationTimedOutException
     */
    public function failure($expectedExitCode = null)
    {
        assertIntegerOrNull($expectedExitCode, 'Expected expected exit code to be an integer or null');

        $actualExitCode = $this->wait();

        if ($expectedExitCode === null && $actualExitCode === 0) {
            throw RuntimeException::format('Expected program to exit with a non-zero exit code, got exit code 0');
        }
        if ($expectedExitCode !== null && $expectedExitCode !== $actualExitCode) {
            throw RuntimeException::format(
                'Expected program to exit with exit code %d, got exit code %d',
                $expectedExitCode,
                $actualExitCode
            );
        }
    }

    /**
     * @throws ExpectationTimedOutException
     */
    private function wait()
    {
        $start = microtime(true);

        while (true) {
            $timeLeft = $this->timeout - (microtime(true) - $start);

            if ($timeLeft <= 0) {

                $this->terminate();

                throw new ExpectationTimedOutException(
                    sprintf(
                        'Program did not terminate within %.3f seconds',
                        $this->timeout
                    ),
                    sprintf(
                        "STDOUT:\n%s\n\nSTDERR:\n%s",
                        preg_replace('~^~', '  ', $this->bufferStdout),
                        preg_replace('~^~', '  ', $this->bufferStderr)
                    )
                );
            }

            $read     = [$this->stdout, $this->stderr];
            $write    = [];
            $except   = [];
            $readable = stream_select($read, $write, $except, 0, min($timeLeft * 1000 * 1000, 0.100 * 1000 * 1000));

            if ($readable === false) {
                throw RuntimeException::format('Stream select error: "%s"', error_get_last()['message']);
            }

            $status = proc_get_status($this->handle);

            if ($status['exitcode'] === -1) {
                continue;
            }

            return $status['exitcode'];
        }

        throw LogicException::format('Should be unreachable code');
    }

    private function terminate()
    {
        @fclose($this->stdin);
        @fclose($this->stdout);
        @fclose($this->stderr);
        @proc_terminate($this->handle);
    }

    public function __destruct()
    {
        $this->terminate();
    }
}
