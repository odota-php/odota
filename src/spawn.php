<?php

namespace Expect\Expect;

/**
 * @param string        $command
 * @param string|null   $workingDirectory
 * @param string[]|null $environmentVariables
 * @param bool          $startWithEmptyEnvironment
 * @return Program
 */
function spawn(
    $command,
    $workingDirectory = null,
    array $environmentVariables = null,
    $startWithEmptyEnvironment = false
) {
    return Program::spawn($command, $workingDirectory, $environmentVariables, $startWithEmptyEnvironment);
}
