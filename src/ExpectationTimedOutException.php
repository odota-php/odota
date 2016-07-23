<?php

namespace Expect\Expect;

final class ExpectationTimedOutException extends RuntimeException
{
    /** @var string */
    private $remainingInBuffer;

    /**
     * @param string          $message
     * @param string          $remainingInBuffer
     * @param \Exception|null $previous
     */
    public function __construct($message, $remainingInBuffer, \Exception $previous = null)
    {
        assertString($remainingInBuffer, 'Expected buffer to be a string, got "%s" of type "%s"');

        parent::__construct($message, 0, $previous);

        $this->remainingInBuffer = $remainingInBuffer;
    }

    /**
     * @return string
     */
    public function getRemainingInBuffer()
    {
        return $this->remainingInBuffer;
    }
}
