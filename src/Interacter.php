<?php

namespace Expecto\Expecto;

final class Interacter
{
    /**
     * The default timeout (100ms) after which expectations time out. You can adjust
     * this per interacter using {timeoutAfter()}.
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
    private $defaultTimeout;

    /**
     * @param          $handle
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
        $this->defaultTimeout = self::DEFAULT_TIMEOUT;
    }

    /**
     * @param resource $handle
     * @param resource $stdout
     * @param resource $stderr
     * @param resource $stdin
     * @return Interacter
     */
    public static function interactWith($handle, $stdout, $stderr, $stdin)
    {
        assertIsResource($handle, 'Expected program handle to be a resource, got type "%s"');
        assertIsResource($stdout, 'Expected program\'s stdout to be a resource, got type "%s"');
        assertIsResource($stderr, 'Expected program\'s stderr to be a resource, got type "%s"');
        assertIsResource($stdin, 'Expected program\'s stdin to be a resource, got type "%s"');

        return new Interacter($handle, $stdout, $stderr, $stdin);
    }

    /**
     * @param float|int $seconds
     * @return static
     */
    public function timeoutAfter($seconds)
    {
        assertFloaty($seconds, 'Expected time-out to be a float, got "%s" of type "%s"');

        $this->defaultTimeout = $seconds;

        return $this;
    }

    /**
     * @param string         $expected
     * @param float|int|null $timeout
     * @return static
     */
    public function expect($expected, $timeout = null)
    {
        $this->expectFromStream('stdout', $expected, $timeout);

        return $this;
    }

    /**
     * @param string         $stream
     * @param string         $expected
     * @param float|int|null $timeout
     */
    private function expectFromStream($stream, $expected, $timeout = null)
    {
        $timeout = $timeout === null ? $this->defaultTimeout : $timeout;

        assertString($expected, 'Expected expected string to be a string, got "%s" of type "%s"');
        assertFloaty($timeout, 'Expected time-out to be a float, got "%s" of type "%s"');

        $start = microtime(true);

        while (true) {
            $timeLeft = $timeout - (microtime(true) - $start);

            if ($timeLeft <= 0) {
                throw new ExpectationTimedOutException(
                    sprintf(
                        'Stream "%s" did not output expected string "%s" within %.3f seconds',
                        $stream,
                        $expected,
                        $timeout
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
     * @param string         $expected
     * @param float|int|null $timeout
     * @return static
     */
    public function expectError($expected, $timeout = null)
    {
        $this->expectFromStream('stderr', $expected, $timeout);

        return $this;
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
        @fclose($this->handle);
    }
}
