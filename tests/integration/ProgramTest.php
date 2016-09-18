<?php

namespace Expect\Expect\IntegrationTest;

use Expect\Expect\ExpectationTimedOutException;
use Expect\Expect\InvalidArgumentException;
use Expect\Expect\Program;
use Expect\Expect\UnexpectedExitCodeException;
use PHPUnit\Framework\TestCase as TestCase;
use function Expect\Expect\spawn;

class ProgramTest extends TestCase
{
    protected function onNotSuccessfulTest($e)
    {
        if ($e instanceof ExpectationTimedOutException) {
            printf(
                "\n\nRemaining in Expect buffer:\n--- STDOUT ---\n%s\n--- STDERR ---\n%s\n\n",
                $e->getRemainingInStdout(),
                $e->getRemainingInStderr()
            );
        }

        parent::onNotSuccessfulTest($e);
    }

    /** @test */
    public function can_expect_a_string_after_some_sleep()
    {
        spawn('sleep 0.100 && echo YAY')
            ->timeoutAfter(0.200)
            ->expect('YAY');
    }

    /** @test */
    public function can_time_out_while_expecting_a_string()
    {
        $this->expectException(ExpectationTimedOutException::class);
        spawn('sleep 0.100 && echo YAY')
            ->timeoutAfter(0.050)
            ->expect('YAY');
    }

    /** @test */
    public function can_expect_a_string_in_stderr()
    {
        spawn('echo YAY >&2')
            ->expectError('YAY');
    }

    /** @test */
    public function can_respond_to_questions()
    {
        spawn('echo -n " > "; read name; echo "Hello, $name!"')
            ->expect(' > ')
            ->sendln('Bob')
            ->expect('Hello, Bob!');
    }

    /** @test */
    public function can_time_out_while_expecting_a_string_after_answering_a_question()
    {
        try {
            spawn('echo -n " > "; read name; echo "Hello, $name!"')
                ->expect(' > ')
                ->sendln('Bob')
                ->expect('Hello, world!');
            $this->fail('Expect ought to have timed out waiting for "Hello, world!"');
        } catch (ExpectationTimedOutException $e) {
            assertContains(
                'Hello, Bob!',
                $e->getRemainingInStdout(),
                sprintf(
                    'Expected "Hello, Bob!" to be present in remaining buffer, got "%s"',
                    $e->getRemainingInStdout()
                )
            );
        }
    }

    /** @test */
    public function can_answer_multiple_questions()
    {
        spawn('echo -n "First name: "; read fname; echo -n " > Last name: "; read lname; echo "Hello, $fname $lname!"')
            ->expect('First name:')
            ->sendln('Bob')
            ->expect('Last name:')
            ->sendln('Saget')
            ->expect('Hello, Bob Saget!')
            ->expectExitCode(0);
    }

    /** @test */
    public function can_expect_a_string_in_two_parts_even_though_its_all_in_the_buffer_already()
    {
        spawn('echo AZ')
            ->expect('A')
            ->expect('Z');
    }

    /** @test */
    public function assert_program_exits_successfully()
    {
        spawn('echo AZ')
            ->expect('A')
            ->expectExitCode(0);
    }

    /** @test */
    public function throws_an_exception_when_program_doesnt_exit_successfully()
    {
        $this->expectException(UnexpectedExitCodeException::class);
        $this->expectExceptionMessage('Expected program to exit with exit code 0, got exit code 1');

        spawn('exit 1')
            ->expectExitCode(0);
    }

    /** @test */
    public function expectation_can_time_out_while_waiting_for_program_to_exit()
    {
        $start = microtime(true);

        try {
            spawn('sleep 1')
                ->timeoutAfter(0.100)
                ->expectExitCode(0);
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
        spawn('exit 1')
            ->expectExitCode(1);
    }

    /** @test */
    public function throws_an_exception_when_program_unexpectedly_succeeds()
    {
        $this->expectException(UnexpectedExitCodeException::class);
        $this->expectExceptionMessage('Expected program to exit with exit code 2, got exit code 0');

        spawn('echo OK')
            ->expectExitCode(2);
    }

    /** @test */
    public function assert_program_exits_with_a_certain_exit_code()
    {
        spawn('exit 2')
            ->expectExitCode(2);
    }

    /** @test */
    public function throws_an_exception_when_program_exits_with_other_exit_code()
    {
        $this->expectException(UnexpectedExitCodeException::class);
        $this->expectExceptionMessage('Expected program to exit with exit code 2, got exit code 1');

        spawn('exit 1')
            ->expectExitCode(2);
    }

    /** @test */
    public function handles_expectations_on_different_streams_after_each_other()
    {
        spawn('echo A; echo B >&2; echo C')
            ->expect('A')
            ->expectError('B')
            ->expect('C');
    }

    /** @test */
    public function allows_expecting_from_stdout_and_stderr_out_of_order()
    {
        spawn('echo AC; echo B >&2')
            ->expect('A')
            ->expectError('B')
            ->expect('C');
    }

    /** @test */
    public function timeout_must_be_greater_than_zero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');
        spawn('echo OK')
            ->timeoutAfter(0);
    }

    /** @test */
    public function timeout_may_not_be_negative()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than zero');
        spawn('echo OK')
            ->timeoutAfter(-1);
    }

    /** @test */
    public function timed_out_exception_contains_string_that_remains_in_buffer()
    {
        try {
            spawn('echo -n NAY')
                ->timeoutAfter(0.050)
                ->expect('YAY');
        } catch (ExpectationTimedOutException $e) {
            assertSame('NAY', $e->getRemainingInStdout());
        }
    }

    /** @test */
    public function when_program_exits_after_having_output_an_expected_string_on_stdout_stderr_is_also_read_and_available_in_the_exceptions_stderr_buffer()
    {
        try {
            spawn('echo -n A; echo -n B >&2')
                ->expect('A')
                ->expectExitCode(0);
        } catch (ExpectationTimedOutException $e) {
            assertSame('', $e->getRemainingInStdout());
            assertSame('B', $e->getRemainingInStderr());
        }
    }

    /** @test */
    public function when_a_program_exits_while_expecting_a_string_on_stdout_stderr_is_still_read_and_available_in_the_exceptions_stderr_buffer()
    {
        try {
            spawn('echo -n A; echo -n Z >&2')
                ->expect('A')
                ->expect('B');
        } catch (ExpectationTimedOutException $e) {
            assertSame('', $e->getRemainingInStdout());
            assertSame('Z', $e->getRemainingInStderr());
        }
    }

    /** @test */
    public function can_answer_single_symfony_question()
    {
        spawn(PHP_BINARY . ' ./tests/bin/symfony-question-single.php -v')
            ->timeoutAfter(1)
            ->expectError('Say yes')
            ->expectError(' > ')
            ->sendln('yes')
            ->expectExitCode(0);
    }

    /** @test */
    public function can_answer_multiple_symfony_question()
    {
        spawn(PHP_BINARY . ' ./tests/bin/symfony-question-multiple.php -v')
            ->timeoutAfter(1)
            ->expectError('Say yes')
            ->expectError(' > ')
            ->sendln('yes')
            ->expectError('Say no')
            ->expectError(' > ')
            ->sendln('no')
            ->expectExitCode(0);
    }

    /** @test */
    public function can_answer_multiple_questions_of_a_symfony_console_application_with_shell_interactive_true()
    {
        spawn(PHP_BINARY . ' ./tests/bin/symfony-console-application.php interview -v', null, ['SHELL_INTERACTIVE' => 'true'])
            ->timeoutAfter(1)
            ->expectError('Say yes')
            ->expectError(' > ')
            ->sendln('yes')
            ->expectExitCode(0);
    }

    /** @test */
    public function the_tests_environment_variables_are_passed_on_by_default()
    {
        $home = getenv('HOME');
        if ($home === false) {
            $this->markTestSkipped("The current environment doesn't have a HOME environment variable");
        }

        spawn(PHP_BINARY . ' ./tests/bin/print-env-home.php')
            ->timeoutAfter(1)
            ->expectError('"' . $home . '"')
            ->expectExitCode(0);
    }

    /** @test */
    public function the_tests_environment_variables_are_not_available_when_starting_with_an_empty_environment()
    {
        $home = getenv('HOME');
        if ($home === false) {
            $this->markTestSkipped("The current environment doesn't have a HOME environment variable");
        }

        spawn(PHP_BINARY . ' ./tests/bin/print-env-home.php', null, null, Program::START_WITH_EMPTY_ENV)
            ->timeoutAfter(1)
            ->expectError('""')
            ->expectExitCode(0);
    }
}
