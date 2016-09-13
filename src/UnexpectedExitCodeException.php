<?php

namespace Expect\Expect;

final class UnexpectedExitCodeException extends RuntimeException
{
    /** @var string */
    private $remainingInStdout;
    /** @var string */
    private $remainingInStderr;

    /**
     * @param string          $message
     * @param string          $remainingInStdout
     * @param string          $remainingInStderr
     * @param \Exception|null $previous
     */
    public function __construct($message, $remainingInStdout, $remainingInStderr, \Exception $previous = null)
    {
        assertString($remainingInStdout, 'Expected what remains in stdout to be a string, got "%s" of type "%s"');
        assertString($remainingInStderr, 'Expected what remains in stderr to be a string, got "%s" of type "%s"');

        parent::__construct($message, 0, $previous);

        $this->remainingInStdout = $remainingInStdout;
        $this->remainingInStderr = $remainingInStderr;
    }

    /**
     * @return string
     */
    public function getRemainingInStdout()
    {
        return $this->remainingInStdout;
    }

    /**
     * @return string
     */
    public function getRemainingInStderr()
    {
        return $this->remainingInStderr;
    }
}
