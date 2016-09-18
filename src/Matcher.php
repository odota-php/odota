<?php

namespace Odota\Odota;

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
