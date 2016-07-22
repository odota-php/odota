<?php

namespace Expecto\Expecto;

/**
 * @param string        $command
 * @param string|null   $workingDirectory
 * @param string[]|null $environmentVariables
 * @return Interacter
 */
function program($command, $workingDirectory = null, array $environmentVariables = null)
{
    return Program::interactWith($command, $workingDirectory, $environmentVariables);
}
