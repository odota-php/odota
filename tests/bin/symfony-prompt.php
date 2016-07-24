#!/usr/bin/env php
<?php

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;

require __DIR__ . '/../../vendor/autoload.php';

$input = new StringInput('');
$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);

$questionHelper = new QuestionHelper();
$answer = $questionHelper->ask($input, $output, new Question("Say yes.\n > "));

assertSame('yes', $answer);
