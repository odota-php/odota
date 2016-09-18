<?php

namespace Odota\Odota;

interface Buffer
{
    /**
     * @return void
     */
    public function read();

    /**
     * Attempts to match with the given Matcher, and drops the amount of bytes matched
     * from this buffer's contents.
     *
     * @param Matcher $matcher
     * @return bool Whether the matcher matched.
     */
    public function matchAndDrop(Matcher $matcher);

    /**
     * @return void
     */
    public function close();

    /**
     * @return resource
     * @internal Just for easy stream_select() calls without fancy OOP.
     */
    public function getStream();

    /**
     * @return string
     */
    public function getContents();
}
