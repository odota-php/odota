<?php

namespace Expecto\Expecto;

/**
 * @param resource $stream
 * @param string   $message
 * @throws InvalidArgumentException
 */
function assertIsResource($stream, $message)
{
    if (!is_resource($stream)) {
        throw InvalidArgumentException::format($message, gettype($stream));
    }
}

/**
 * @param mixed  $value
 * @param string $message
 * @throws InvalidArgumentException
 */
function assertNonBlankString($value, $message)
{
    if (!is_string($value) || $value === '') {
        throw InvalidArgumentException::format($message, stringify($value), gettype($value));
    }
}

/**
 * @param mixed  $value
 * @param string $message
 * @throws InvalidArgumentException
 */
function assertString($value, $message)
{
    if (!is_string($value)) {
        throw InvalidArgumentException::format($message, stringify($value), gettype($value));
    }
}

/**
 * @param mixed  $value
 * @param string $message
 * @throws InvalidArgumentException
 */
function assertFloaty($value, $message)
{
    if (!is_float($value) && !is_int($value)) {
        throw InvalidArgumentException::format($message, stringify($value), gettype($value));
    }
}

/**
 * @param array $environmentVariables
 * @throws InvalidArgumentException
 */
function assertArrayOfEnvironmentVariables($environmentVariables)
{
    foreach ($environmentVariables as $name => $value) {
        assertNonBlankString($name, 'Environment variable ought to be a non-empty string, got "%s" of type "%s"');
        assertString($value, 'Environment variable value ought to be a string, got "%s" of type "%s"');
    }
}

/**
 * Stringifies any type of value. Copied from beberlei/assert.
 *
 *     Copyright (c) 2011-2013, Benjamin Eberlei
 *     All rights reserved.
 *
 *     Redistribution and use in source and binary forms, with or without
 *     modification, are permitted provided that the following conditions are met:
 *
 *     - Redistributions of source code must retain the above copyright notice, this
 *     list of conditions and the following disclaimer.
 *     - Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * @param mixed $value
 * @return string
 */
function stringify($value)
{
    if (is_bool($value)) {
        return $value ? '<TRUE>' : '<FALSE>';
    }

    if (is_scalar($value)) {
        $val = (string) $value;

        if (strlen($val) > 100) {
            $val = substr($val, 0, 97) . '...';
        }

        return $val;
    }

    if (is_array($value)) {
        return '<ARRAY>';
    }

    if (is_object($value)) {
        return get_class($value);
    }

    if (is_resource($value)) {
        return '<RESOURCE>';
    }

    if ($value === null) {
        return '<NULL>';
    }

    throw new \LogicException('Value of unknown type encountered');
}
