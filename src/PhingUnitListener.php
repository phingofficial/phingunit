<?php

namespace Phing\PhingUnit;

/**
 * Interface PhingUnitListener
 * @package Phing\PhingUnit
 */
interface PhingUnitListener
{
    /**
     * Set a reference to the PhingUnit task executing the tests, this
     * provides access to the containing project, target or Phing's
     * logging system.
     * @param \Task $t the parent task
     */
    public function setParentTask(\Task $t);

    /**
     * Set a reference to the Project instance currently executing the
     * test target.
     *
     * <p>This provides access to the logging system or the properties
     * of the project under test.  Note that different test targets
     * will be executed in different Phing Project instances.</p>
     * @param \Project $p the test project
     */
    public function setCurrentTestProject(\Project $p);

    /**
     * Invoked once per build file, before any targets get executed.
     * @param \Project $testProject the project
     * @param string $buildFile the build file
     */
    public function startTestSuite(\Project $testProject, string $buildFile);

    /**
     * Invoked once per build file, after all targets have been executed.
     * @param \Project $testProject the project
     * @param string $buildFile the build file
     */
    public function endTestSuite(\Project $testProject, string $buildFile);

    /**
     * Invoked before a test target gets executed.
     * @param string $target name of the target
     */
    public function startTest(string $target);

    /**
     * Invoked after a test target has been executed.
     * @param string $target name of the target
     */
    public function endTest(string $target);

    /**
     * Invoked if an assert tasked caused an error during execution.
     * @param string $target name of the target
     * @param AssertionFailedException $ae the failure
     */
    public function addFailure(string $target, AssertionFailedException $ae);

    /**
     * Invoked if any error other than a failed assertion occured
     * during execution.
     * @param string $target name of the target
     * @param \Throwable $ae the error
     */
    public function addError(string $target, \Throwable $ae);
}
