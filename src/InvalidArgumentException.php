<?php

namespace Expect\Expect;

class InvalidArgumentException extends \InvalidArgumentException
{
    /**
     * @param string  $message
     * @param mixed[] ...$substitutions
     * @return InvalidArgumentException
     */
    public static function format($message, ...$substitutions)
    {
        return new static(sprintf($message, ...$substitutions));
    }
}
