<?php

namespace Expect\Expect;

interface Matcher
{
    /**
     * @param string $string
     * @return int The amount of bytes matched.
     */
    public function match($string);

    /**
     * @return string
     */
    public function __toString();
}
