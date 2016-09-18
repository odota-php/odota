<?php

namespace Expect\Expect;

/**
 * @param string        $command
 * @param string|null   $workingDirectory
 * @param string[]|null $environmentVariables
 * @return Program
 */
function spawn(
    $command,
    $workingDirectory = null,
    array $environmentVariables = null
) {
    return Program::spawn($command, $workingDirectory, $environmentVariables, Program::COPY_ENV);
}

/**
 * @param string        $command
 * @param string|null   $workingDirectory
 * @param string[]|null $environmentVariables
 * @return Program
 */
function spawnWithEmptyEnv(
    $command,
    $workingDirectory = null,
    array $environmentVariables = null
) {
    return Program::spawn($command, $workingDirectory, $environmentVariables, Program::START_WITH_EMPTY_ENV);
}
