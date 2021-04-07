<?php

namespace Phing\PhingUnit;

/**
 * Exits the active build, giving an additional message if the single
 * nested condition fails or if there is no condition at all.
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class AssertFalse extends AssertTask
{
    public function main()
    {
        $this->message = $this->message === AssertionFailedException::DEFAULT_MESSAGE
            ? $this->message
            : 'Assertion failed';
        $this->createIsFalse();
        parent::main();
    }
}
