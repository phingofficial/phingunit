<?php

namespace Phing\PhingUnit;

use AssertionError;
use Phing\Exception\BuildException;
use Phing\Project;

/**
 * Class PhingUnitScriptRunner
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class PhingUnitScriptRunner
{
    /**
     * name of the magic setUp target.
     */
    private const SETUP = "setUp";

    /**
     * prefix that identifies test targets.
     */
    private const TEST = "test";

    /**
     * name of the magic tearDown target.
     */
    private const TEARDOWN = "tearDown";

    /**
     * name of the magic suiteSetUp target.
     */
    private const SUITESETUP = "suiteSetUp";

    /**
     * name of the magic suiteTearDown target.
     */
    private const SUITETEARDOWN = "suiteTearDown";
    /**
     * @var ProjectFactory
     */
    private $prjFactory;
    /**
     * Indicates if the startSuite method has been invoked.  Use to fail fast if the
     * the caller forget to call the startSuite method
     * @var bool
     */
    private $isSuiteStarted;
    /**
     * @var bool
     */
    private $hasSetUp;
    /**
     * @var bool
     */
    private $hasTearDown;
    /**
     * @var bool
     */
    private $hasSuiteSetUp;
    /**
     * @var bool
     */
    private $hasSuiteTearDown;
    /**
     * @var array
     */
    private $testTargets;

    /** @var Project */
    private $project;
    /**
     * @var bool
     */
    private $projectIsDirty;

    /**
     * PhingUnitScriptRunner constructor.
     * @param ProjectFactory $prjFactory
     */
    public function __construct(ProjectFactory $prjFactory)
    {
        $this->prjFactory = $prjFactory;
        $newProject = $this->getCurrentProject();
        $targets = $newProject->getTargets();
        $this->hasSetUp = array_key_exists(self::SETUP, $targets);
        $this->hasTearDown = array_key_exists(self::TEARDOWN, $targets);
        $this->hasSuiteSetUp = array_key_exists(self::SUITESETUP, $targets);
        $this->hasSuiteTearDown = array_key_exists(self::SUITETEARDOWN, $targets);
        $this->testTargets = [];
        foreach ($targets as $name => $target) {
            if ($name !== self::TEST) {
                {
                    $this->testTargets[] = $name;
                }
            }
        }
    }

    public function getCurrentProject(): Project
    {
        //Method is final because it is called from the constructor
        if ($this->project === null) {
            $this->project = $this->prjFactory->createProject();
            $this->projectIsDirty = false;
        }
        return $this->project;
    }

    /**
     * @return string[] List of test targets of the script file
     */
    public function getTestTartgets(): array
    {
        return $this->testTargets;
    }

    /**
     * Provides the name of the active script.
     * @return string $name of the project
     */
    public function getName(): string
    {
        return $this->getCurrentProject()->getName();
    }

    /**
     * Executes the suite.
     * @param array $suiteTargets An ordered list of test targets.  It must be a sublist of getTestTargets
     * @param PhingUnitExecutionNotifier $notifier is notified on test progress
     */
    public function runSuite(array $suiteTargets, PhingUnitExecutionNotifier $notifier): void
    {
        $caught = null;
        try {
            if (!$this->startSuite($notifier)) {
                return;
            }
            foreach ($suiteTargets as $name => $target) {
                $this->runTarget($name, $notifier);
            }
        } catch (\Throwable $e) {
            $caught = $e;
        } finally {
            $this->endSuite($caught, $notifier);
        }
    }

    /**
     * Executes the suiteSetUp target if presents and report any execution error.
     * <p>A failure is reported to the notifier and by returning false.
     * Note that if the method return false, you are not allowed to run targets.</p>
     * @param PhingUnitExecutionNotifier $notifier
     * @return false in case of execution failure.  true in case of success.
     */
    private function startSuite(PhingUnitExecutionNotifier $notifier): bool
    {
        $this->getCurrentProject()->fireBuildStarted();
        if ($this->hasSuiteSetUp) {
            try {
                $newProject = $this->getCleanProject();
                $newProject->executeTarget(self::SUITESETUP);
            } catch (BuildException $e) {
                $notifier->fireStartTest(self::SUITESETUP);
                $this->fireFailOrError(self::SUITESETUP, $e, $notifier);
                return false;
            }
        }
        $this->isSuiteStarted = true; //set to true only if suiteSetUp executed properly.
        return true;
    }

    /**
     * Get a project that has not yet been used in order to execute a target on it.
     */
    private function getCleanProject(): Project
    {
        if ($this->project === null || $this->projectIsDirty) {
            $this->project = $this->prjFactory->createProject();
        }
        //we already set isDirty to true in order to make sure we didn't reuse
        //this project next time getCleanProject is called.  
        $this->projectIsDirty = true;
        return $this->project;
    }

    /**
     * Try to see whether the BuildException e is an AssertionFailedException
     * or is caused by an AssertionFailedException. If so, fire a failure for
     * given targetName.  Otherwise fire an error.
     * @param string $targetName
     * @param BuildException $e
     * @param PhingUnitExecutionNotifier $notifier
     */
    private function fireFailOrError(
        string $targetName,
        BuildException $e,
        PhingUnitExecutionNotifier $notifier
    ): void {
        $failed = false;
        $t = $e;
        while ($t !== null && $t instanceof BuildException) {
            if ($t instanceof AssertionFailedException) {
                $failed = true;
                $notifier->fireFail($targetName, $t);
                break;
            }
            $t = $t->getMessage();
        }

        if (!$failed) {
            $notifier->fireError($targetName, $e);
        }
    }

    /**
     * Run the specific test target, possibly between the setUp and tearDown targets if
     * it exists.  Exception or failures are reported to the notifier.
     * @param string $name name of the test target to execute.
     * @param PhingUnitExecutionNotifier $notifier will receive execution notifications.
     */
    private function runTarget(string $name, PhingUnitExecutionNotifier $notifier): void
    {
        if (!$this->isSuiteStarted) {
            throw new AssertionError();
        }
        $newProject = $this->getCleanProject();
        $v = [];
        if ($this->hasSetUp) {
            $v[] = self::SETUP;
        }
        $v[] = $name;
        // create and register a logcapturer on the newProject
        $lc = new LogCapturer($newProject);
        $newProject->addBuildListener($lc);
        try {
            $notifier->fireStartTest($name);
            $newProject->executeTargets($v);
        } catch (BuildException $e) {
            $this->fireFailOrError($name, $e, $notifier);
        } finally {
            // fire endTest here instead of the endTarget
            // event, otherwise an error would be
            // registered after the endTest event -
            // endTarget is called before this method's catch block
            // is reached.
            $notifier->fireEndTest($name);
            // clean up
            if ($this->hasTearDown) {
                try {
                    $newProject->executeTarget(self::TEARDOWN);
                } catch (BuildException $e) {
                    $this->fireFailOrError($name, $e, $notifier);
                }
            }
        }
    }

    /**
     * Executes the suiteTearDown target if presents and report any execution error.
     * @param \Throwable $caught Any internal exception triggered (and caught) by the caller indicating that
     * the execution could not be invoked as expected.
     * @param PhingUnitExecutionNotifier $notifier will receive execution notifications.
     */
    private function endSuite(\Throwable $caught, PhingUnitExecutionNotifier $notifier): void
    {
        if ($this->hasSuiteTearDown) {
            try {
                $newProject = $this->getCleanProject();
                $newProject->executeTarget(self::SUITETEARDOWN);
            } catch (BuildException $e) {
                $notifier->fireStartTest(self::SUITETEARDOWN);
                $this->fireFailOrError(self::SUITETEARDOWN, $e, $notifier);
            }
        }
        $this->getCurrentProject()->fireBuildFinished($caught);
        $this->isSuiteStarted = false;
    }
}
