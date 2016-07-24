<?php

namespace Expect\Expect\IntegrationTest;

use Expect\Expect\ExpectationTimedOutException;
use PHPUnit\Framework\TestCase;
use function Expect\Expect\program;

final class SymfonyConsoleProgramTest extends TestCase
{
    /** @test */
    public function it_can_answer_questions()
    {
        program('./tests/bin/symfony-prompt.php -v')
            ->expect('Say yes')
            ->expect(' > ')
            ->sendln('yes')
            ->success();
    }

    protected function onNotSuccessfulTest($e)
    {
        if ($e instanceof ExpectationTimedOutException) {
            printf(
                "\n\nRemaining in Expect buffer:\n---\n%s\n---\n\n",
                $e->getRemainingInOutputBuffer()
            );
        }

        parent::onNotSuccessfulTest($e);
    }
}
