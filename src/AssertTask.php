<?php

namespace Phing\PhingUnit;

/**
 * Exits the active build, giving an additional message if the single
 * nested condition fails or if there is no condition at all.
 *
 * @package Phing\PhingUnit
 */
class AssertTask extends \ConditionBase
{
    /**
     * Message to use when the assertion fails.
     */
    private $message = AssertionFailedException::DEFAULT_MESSAGE;

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
            throw new \BuildException('You must not specify more than one condition', $this->getLocation());
        }
        if ($count < 1 || !(isset($this->getConditions()[0]) && ($this->getConditions()[0])->evaluate())) {
            throw new AssertionFailedException($this->message, $this->getLocation());
        }
    }
}
