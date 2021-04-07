<?php

namespace Phing\PhingUnit;

use Phing\Exception\BuildException;
use Phing\Task\System\SequentialTask;

/**
 * Expects the nested tasks to throw a BuildException and optinally
 * asserts the message of that exception.
 *
 * <p>Throws a AssertFailedException if the nested tasks do not throw
 * the expected BuildException.</p>
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class ExpectFailureTask extends SequentialTask
{
    private $expectedMessage;
    private $message;

    /**
     * The exception message to expect.
     * @param string $m the exception message to expect
     */
    public function setExpectedMessage(string $m): void
    {
        $this->expectedMessage = $m;
    }

    /**
     * The message to use in the AssertionFailedException if the nested
     * tasks fail to raise the "correct" exception.
     * @param string $m message to use in the AssertionFailedException
     */
    public function setMessage(string $m): void
    {
        $this->message = $m;
    }

    public function main()
    {
        $thrown = false;
        try {
            parent::main();
        } catch (BuildException $e) {
            $thrown = true;
            $caughtMessage = $e->getMessage();
            if ($this->expectedMessage !== null &&
                ($caughtMessage === null
                    || strpos($caughtMessage, $this->expectedMessage) === false)) {
                if ($this->message === null) {
                    throw new AssertionFailedException(
                        'Expected build failure '
                        . "with message '"
                        . $this->expectedMessage
                        . "' but was '"
                        . $caughtMessage . "'", $e);
                }

                throw new AssertionFailedException($this->message, $e);
            }
        }

        if (!$thrown) {
            if ($this->message === null) {
                throw new AssertionFailedException('Expected build failure');
            }

            throw new AssertionFailedException($this->message);
        }
    }
}
