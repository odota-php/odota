<?php

namespace Odota\Odota;

class LogicException extends \LogicException
{
    /**
     * @param string  $message
     * @param mixed[] ...$substitutions
     * @return LogicException
     */
    public static function format($message, ...$substitutions)
    {
        return new static(sprintf($message, ...$substitutions));
    }
}
