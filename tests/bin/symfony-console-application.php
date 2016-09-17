<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

require __DIR__ . '/../../vendor/autoload.php';

class QuestionCommand extends Command
{
    protected function configure()
    {
        $this->setName('interview');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = new QuestionHelper();
        $answer = $questionHelper->ask($input, $output, new Question("Say yes.\n > "));

        assertSame('yes', $answer);
    }

}

$application = new Application();
$application->add(new QuestionCommand());
$application->run();
