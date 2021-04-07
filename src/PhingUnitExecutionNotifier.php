<?php

namespace Phing\PhingUnit;

/**
 * Interface PhingUnitExecutionNotifier
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
interface PhingUnitExecutionNotifier
{
    /**
     * invokes start on all registered test listeners.
     * @param string $targetName the name of the target.
     */
    public function fireStartTest(string $targetName);

    /**
     * invokes addFailure on all registered test listeners.
     * @param string $targetName the name of the failed target.
     * @param AssertionFailedException $ae the associated AssertionFailedException.
     */
    public function fireFail(string $targetName, AssertionFailedException $ae);

    /**
     * invokes addError on all registered test listeners.
     * @param string $targetName the name of the failed target.
     * @param \Throwable $t the associated Throwable.
     */
    public function fireError(string $targetName, \Throwable $t);

    /**
     * invokes endTest on all registered test listeners.
     * @param string $targetName the name of the current target.
     */
    public function fireEndTest(string $targetName);
}
