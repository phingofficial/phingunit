<?php

namespace Phing\PhingUnit;

use Phing\Exception\BuildException;
use Phing\Task\System\Condition\ConditionBase;

/**
 * Exits the active build, giving an additional message if the single
 * nested condition fails or if there is no condition at all.
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class AssertTask extends ConditionBase
{
    /**
     * Message to use when the assertion fails.
     */
    protected $message = AssertionFailedException::DEFAULT_MESSAGE;

    /**
     * Message to use when the assertion fails.
     * @param string $value message to use when the assertion fails
     */
    public function setMessage(string $value): void
    {
        $this->message = $value;
    }

    public function main()
    {
        $count = $this->countConditions();
        if ($count > 1) {
            throw new BuildException('You must not specify more than one condition', $this->getLocation());
        }
        if ($count < 1 || !(isset($this->getConditions()[0]) && ($this->getConditions()[0])->evaluate())) {
            throw new AssertionFailedException($this->message, $this->getLocation());
        }
    }
}
