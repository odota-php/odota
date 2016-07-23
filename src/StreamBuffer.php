<?php

namespace Expect\Expect;

final class StreamBuffer implements Buffer
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @var string
     */
    private $contents;

    /**
     * @param resource $stream
     */
    public function __construct($stream)
    {
        assertIsResource($stream, 'Buffer stream ought to be a resource, got type "%s"');

        $this->stream = $stream;
        $this->contents = '';
    }

    public function read()
    {
        do {
            $bytes = fread($this->stream, 4096);
            $this->contents .= $bytes;
        } while ($bytes !== '');
    }

    public function matchAndDrop(Matcher $matcher)
    {
        $bytesMatched = $matcher->match($this->contents);

        $this->contents = substr($this->contents, $bytesMatched);

        return $bytesMatched > 0;
    }

    public function close()
    {
        fclose($this->stream);
    }

    public function getStream()
    {
        return $this->stream;
    }

    public function getContents()
    {
        return $this->contents;
    }
}
