<?php

namespace Expect\Expect\Buffer;

use function Expect\Expect\assertIsResource;
use Expect\Expect\Buffer;
use Expect\Expect\LogicException;
use Expect\Expect\Matcher;

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

        $contentsLength = strlen($this->contents);
        if ($bytesMatched > $contentsLength) {
            throw new LogicException(sprintf('Matcher could not have matched a string longer (%d) than the length of the buffer\'s contents (%d)', $bytesMatched, $contentsLength));
        } elseif ($bytesMatched === $contentsLength) {
            $this->contents = '';
        } else {
            $this->contents = $bytesMatched >= $contentsLength ? '' : substr($this->contents, $bytesMatched);
        }

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
