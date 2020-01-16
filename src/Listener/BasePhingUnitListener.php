<?php

namespace Phing\PhingUnit\Listener;

use Phing\PhingUnit\AssertionFailedException;
use Phing\PhingUnit\PhingUnitListener;

abstract class BasePhingUnitListener implements PhingUnitListener
{
    /**
     * keeps track of the numer of executed targets, the failures an errors.
     */
    protected $runCount, $failureCount, $errorCount;
    /**
     * time for the starts of the current test-suite and test-target.
     */
    protected $start, $testStart;
    /**
     * Directory to write reports to.
     */
    private $toDir;
    /**
     * Extension for report files.
     */
    private $extension;
    /**
     * Where to send log.
     */
    private $logTo;
    
    /** @var \Task */
    private $parentTask;
    /** @var \Project */
    private $currentTest;
    /**
     * The minimum level a log message must be logged at to be
     * included in the output.
     */
    private $logLevel;

    public function setParentTask(\Task $t)
    {
        $this->parentTask = $t;
    }

    public function startTestSuite(\Project $testProject, string $buildFile)
    {
        $this->start = \Phing::currentTimeMillis();
        $this->runCount = $this->failureCount = $this->errorCount = 0;
    }

    public function startTest(string $target)
    {
        $this->testStart = \Phing::currentTimeMillis();
        $this->runCount++;
    }

    public function addFailure(string $target, AssertionFailedException $ae)
    {
        $this->failureCount++;
    }

    public function addError(string $target, \Throwable $ae)
    {
        $this->errorCount++;
    }

    public function setCurrentTestProject(\Project $p)
    {
        $this->currentTest = $p;
        $p->addBuildListener(new LogGrabber());
    }
}
