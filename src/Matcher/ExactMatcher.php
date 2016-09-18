<?php

namespace Odota\Odota\Matcher;

use Odota\Odota\Matcher;
use function Odota\Odota\assertNonBlankString;
use function Odota\Odota\assertString;

final class ExactMatcher implements Matcher
{
    /**
     * @var string
     */
    private $toMatch;

    /**
     * @param string $toMatch
     */
    public function __construct($toMatch)
    {
        assertNonBlankString($toMatch, 'String to match ought to be a non-empty string, got "%s" of type "%s"');

        $this->toMatch = $toMatch;
    }

    public function match($string)
    {
        assertString($string, 'String to match against ought to be a non-empty string, got "%s" of type "%s"');

        $position = strpos($string, $this->toMatch, 0);

        if ($position === false) {
            return 0;
        }

        return $position + strlen($this->toMatch);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('ExactMatcher("%s")', $this->toMatch);
    }
}
