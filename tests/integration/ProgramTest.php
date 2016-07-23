<?php

namespace Expect\Expect\IntegrationTest;

use Expect\Expect\ExpectationTimedOutException;
use Expect\Expect\RuntimeException;
use PHPUnit\Framework\TestCase as TestCase;
use function Expect\Expect\program;

class ProgramTest extends TestCase
{
    /** @test */
    public function can_expect_a_string_after_some_sleep()
    {
        program('sleep 0.100 && echo YAY')
            ->timeoutAfter(0.200)
            ->expect('YAY');
    }

    /** @test */
    public function can_time_out_while_expecting_a_string()
    {
        $this->expectException(ExpectationTimedOutException::class);
        program('sleep 0.100 && echo YAY')
            ->timeoutAfter(0.050)
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
                ->expect(' > ')
                ->sendln('Bob')
                ->expect('Hello, world!');
            $this->fail('Expect ought to have timed out waiting for "Hello, world!"');
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
            ->expect('A')
            ->expect('Z');
    }

    /** @test */
    public function assert_program_exits_successfully()
    {
        program('echo AZ')
            ->expect('A')
            ->success();
    }

    /** @test */
    public function throws_an_exception_when_program_doesnt_exit_successfully()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected program to exit successfully, got exit code 1');

        program('exit 1')
            ->success();
    }

    /** @test */
    public function expectation_can_time_out_while_waiting_for_program_to_exit()
    {
        $start = microtime(true);

        try {
            program('sleep 1')
                ->timeoutAfter(0.100)
                ->success();
            $this->fail('Program shouldn\'t have exited within 100 milliseconds');
        } catch (ExpectationTimedOutException $e) {
            // Great! Now we still have to check whether this didn't occur after 1 second,
            // instead of 100 milliseconds.
        }

        $end = microtime(true);
        $elapsed = $end - $start;

        if ($elapsed > 0.150) {
            // In case somehow Expect waited for sleep to exit after 1 second.
            $this->fail(
                sprintf(
                    'Waiting for program exit ought to have timed out after 100ms, but it took %.3f seconds',
                    $elapsed
                )
            );
        }
    }

    /** @test */
    public function assert_program_exits_with_an_nonzero_exit_code()
    {
        program('exit 1')
            ->failure();
    }

    /** @test */
    public function throws_an_exception_when_program_unexpectedly_succeeds()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected program to exit with a non-zero exit code, got exit code 0');

        program('echo OK')
            ->failure();
    }

    /** @test */
    public function assert_program_exits_with_a_certain_exit_code()
    {
        program('exit 2')
            ->failure(2);
    }

    /** @test */
    public function throws_an_exception_when_program_exits_with_other_exit_code()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected program to exit with exit code 2, got exit code 1');

        program('exit 1')
            ->failure(2);
    }

    /** @test */
    public function handles_expectations_on_different_streams_after_each_other()
    {
        program('echo A; echo B >&2; echo C')
            ->expect('A')
            ->expectError('B')
            ->expect('C');
    }
}
