<?php

namespace Expect\Expect;

final class ExpectationTimedOutException extends RuntimeException
{
    /** @var string */
    private $inOutputBuffer;

    /**
     * @param string          $message
     * @param string          $inOutputBuffer
     * @param \Exception|null $previous
     */
    public function __construct($message, $inOutputBuffer, \Exception $previous = null)
    {
        assertString($inOutputBuffer, 'Expected output buffer to be a string, got "%s" of type "%s"');

        parent::__construct($message, 0, $previous);

        $this->inOutputBuffer = $inOutputBuffer;
    }

    /**
     * @return string
     */
    public function getRemainingInOutputBuffer()
    {
        return $this->inOutputBuffer;
    }
}
