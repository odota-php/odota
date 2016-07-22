<?php

namespace Expecto\Expecto;

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
    private $buffer;
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
        $this->handle         = $handle;
        $this->stdout         = $stdout;
        $this->stderr         = $stderr;
        $this->stdin          = $stdin;
        $this->buffer         = '';
        $this->timeout        = self::DEFAULT_TIMEOUT;
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
                    $this->buffer
                );
            }

            $read     = [$this->$stream];
            $write    = [];
            $except   = [];
            $readable = stream_select($read, $write, $except, 0, $timeLeft);

            if ($readable === false) {
                throw RuntimeException::format('Stream select error: "%s"', error_get_last()['message']);
            } elseif ($readable === 0) {
                continue;
            }

            do {
                $bytes = fread($this->$stream, 4096);
                $this->buffer .= $bytes;
            } while ($bytes !== '');

            $position = strpos($this->buffer, $expected);
            if ($position === false) {
                continue;
            }

            $this->buffer = substr($this->buffer, strlen($position));

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

    public function __destruct()
    {
        proc_close($this->handle);
    }
}
