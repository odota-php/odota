<?php

namespace Expect\Expect;

class RuntimeException extends \RuntimeException
{
    /**
     * @param string  $message
     * @param mixed[] ...$substitutions
     * @return RuntimeException
     */
    public static function format($message, ...$substitutions)
    {
        return new static(sprintf($message, ...$substitutions));
    }
}
