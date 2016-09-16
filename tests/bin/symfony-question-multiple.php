<?php

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Question\Question;

require __DIR__ . '/../../vendor/autoload.php';

$input = new StringInput('');
$input->setInteractive(true);
$output = new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG);

$questionHelper = new QuestionHelper();

$yes = $questionHelper->ask($input, $output, new Question("Say yes.\n > "));
assertSame('yes', $yes);

$no = $questionHelper->ask($input, $output, new Question("Say no.\n > "));
assertSame('no', $no);
