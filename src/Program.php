<?php

namespace Expecto\Expecto;

final class Program
{
    /**
     * @param string        $command
     * @param string|null   $workingDirectory
     * @param string[]|null $environmentVariables
     * @return Interacter
     */
    public static function interactWith($command, $workingDirectory = null, array $environmentVariables = null)
    {
        $workingDirectory     = $workingDirectory ?: getcwd();
        $environmentVariables = $environmentVariables ?: [];

        assertNonBlankString(
            $workingDirectory,
            'Working directory ought to be a non-empty string, got "%s" of type "%s"'
        );
        assertArrayOfEnvironmentVariables($environmentVariables);

        $process = proc_open(
            $command,
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
            ],
            $pipes,
            $workingDirectory,
            $environmentVariables
        );
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        return Interacter::interactWith($process, $pipes[1], $pipes[2], $pipes[0]);
    }
}
