<?php

namespace Expect\Expect;

use Expect\Expect\Buffer\StreamBuffer;
use Expect\Expect\Matcher\ExactMatcher;

final class Program
{
    const DESCRIPTOR_STDIN = 0;
    const DESCRIPTOR_STDOUT = 1;
    const DESCRIPTOR_STDERR = 2;

    /**
     * The default timeout (100ms) after which expectations time out. You can adjust
     * this per program using {timeoutAfter()}.
     */
    const DEFAULT_TIMEOUT = 0.100;

    /** @var resource */
    private $handle;
    /** @var Buffer */
    private $output;
    /** @var resource */
    private $stdin;
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
        $workingDirectory = $workingDirectory ?: getcwd();
        $environmentVariables = $environmentVariables ?: [];

        assertNonBlankString(
            $workingDirectory,
            'Working directory ought to be a non-empty string, got "%s" of type "%s"'
        );
        assertArrayOfEnvironmentVariables($environmentVariables);

        $process = proc_open(
            $command,
            [
                self::DESCRIPTOR_STDIN  => ['pipe', 'r'],
                self::DESCRIPTOR_STDOUT => ['pty'],
                self::DESCRIPTOR_STDERR => ['pty'],
            ],
            $pipes,
            $workingDirectory,
            $environmentVariables
        );
        stream_set_blocking($pipes[self::DESCRIPTOR_STDOUT], 0);

        return new Program($process, new StreamBuffer($pipes[self::DESCRIPTOR_STDOUT]), $pipes[self::DESCRIPTOR_STDIN]);
    }

    /**
     * @param resource $handle
     * @param Buffer   $output
     * @param resource $stdin
     */
    private function __construct($handle, Buffer $output, $stdin)
    {
        $this->handle = $handle;
        $this->output = $output;
        $this->stdin = $stdin;
        $this->timeout = self::DEFAULT_TIMEOUT;
    }

    /**
     * @param float|int $seconds
     * @return $this
     */
    public function timeoutAfter($seconds)
    {
        assertFloaty($seconds, 'Expected time-out to be a float, got "%s" of type "%s"');
        if ($seconds <= 0) {
            throw InvalidArgumentException::format('Time-out must be greater than zero, got %f', $seconds);
        }

        $this->timeout = $seconds;

        return $this;
    }

    /**
     * @param string $expected
     * @return $this
     */
    public function expect($expected)
    {
        assertString($expected, 'Expected expected string to be a string, got "%s" of type "%s"');

        $this->expectFromStream('output', new ExactMatcher($expected));

        return $this;
    }

    /**
     * @param string  $stream
     * @param Matcher $matcher
     */
    private function expectFromStream($stream, Matcher $matcher)
    {
        /** @var Buffer $buffer */
        $buffer = &$this->$stream;

        $start = microtime(true);

        while (true) {
            $bytesMatched = $buffer->matchAndDrop($matcher);
            if ($bytesMatched > 0) {
                return;
            }

            $timeLeft = $this->timeout - (microtime(true) - $start);

            if ($timeLeft <= 0) {
                throw new ExpectationTimedOutException(
                    sprintf(
                        'Stream "%s" did not output expected "%s" within %.3f seconds',
                        $stream,
                        $matcher,
                        $this->timeout
                    ),
                    $this->output->getContents()
                );
            }

            $read = [$this->output->getStream()];
            $write = [];
            $except = [];
            $readable = stream_select($read, $write, $except, 0, $timeLeft * 1000 * 1000);

            if ($readable === false) {
                throw RuntimeException::format('Stream select error: "%s"', error_get_last()['message']);
            } elseif ($readable === 0) {
                continue;
            }

            $this->output->read();
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
                throw new ExpectationTimedOutException(
                    sprintf(
                        'Program did not terminate within %.3f seconds',
                        $this->timeout
                    ),
                    $this->output->getContents()
                );
            }

            $read = [$this->output->getStream()];
            $write = [];
            $except = [];
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
        fclose($this->stdin);
        $this->output->close();
        proc_terminate($this->handle);
    }

    public function __destruct()
    {
        $this->terminate();
    }
}
