<?php

namespace Expecto\Expecto\IntegrationTest;

use Expecto\Expecto\ExpectationTimedOutException;
use PHPUnit\Framework\TestCase as TestCase;
use function Expecto\Expecto\program;

class ProgramTest extends TestCase
{
    /** @test */
    public function can_expect_a_string_after_some_sleep()
    {
        program('sleep 0.005 && echo YAY')
            ->timeoutAfter(0.010)
            ->expect('YAY');
    }

    /** @test */
    public function can_time_out_while_expecting_a_string()
    {
        $this->expectException(ExpectationTimedOutException::class);
        program('sleep 0.010 && echo YAY')
            ->timeoutAfter(0.005)
            ->expect('YAY');
    }

    /** @test */
    public function can_expect_a_string_in_stderr()
    {
        program('echo YAY >&2')
            ->expectError('YAY');
    }

    /** @test */
    public function can_respond_to_questions()
    {
        program('echo -n " > "; read name; echo "Hello, $name!"')
            ->expect(' > ')
            ->sendln('Bob')
            ->expect('Hello, Bob!');
    }

    /** @test */
    public function can_time_out_while_expecting_a_string_after_answer_a_question()
    {
        try {
            program('echo -n " > "; read name; echo "Hello, $name!"')
                ->timeoutAfter(0.010)
                ->expect(' > ')
                ->sendln('Bob')
                ->expect('Hello, world!');
            $this->fail('Expecto ought to have timed out waiting for "Hello, world!"');
        } catch (ExpectationTimedOutException $e) {
            assertContains(
                'Hello, Bob!',
                $e->getRemainingInBuffer(),
                sprintf(
                    'Expected "Hello, Bob!" to be present in remaining buffer, got "%s"',
                    $e->getRemainingInBuffer()
                )
            );
        }
    }

    /** @test */
    public function can_expect_a_string_in_two_parts_even_though_its_all_in_the_buffer_already()
    {
        program('echo AZ')
            ->timeoutAfter(0.010)
            ->expect('A')
            ->expect('Z');
    }
}
