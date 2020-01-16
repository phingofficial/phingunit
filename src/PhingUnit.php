<?php

namespace Phing\PhingUnit;

/**
 * Run every target whose name starts with "test" in a set of build files.
 *
 * <p>Run the "setUp" target before each of them if present, same for
 * "tearDown" after each "test*" target (targets named just "test" are
 * ignored).  If a target throws an AssertionFailedException, the test
 * has failed; any other exception is considered an error (although
 * BuildException will be scanned recursively for nested
 * AssertionFailedExceptions).</p>
 */
class PhingUnit extends \Task
{
    /**
     * Message to print if an error or failure occured.
     */
    public const ERROR_TESTS_FAILED = 'Tests failed with ';

    /**
     * Message if no tests have been specified.
     */
    public const ERROR_NO_TESTS = 'You must specify build files to test.';

    /**
     * Message if non-File resources have been specified.
     */
    public const ERROR_NON_FILES = 'Only file system resources are supported.';

    /** @var \FileSet */
    private $buildFiles;

    /**
     * has a failure occured?
     */
    private $failures = 0;

    /**
     * has an error occured?
     */
    private $errors = 0;

    /**
     * stop testing if an error or failure occurs?
     */
    private $failOnError = true;

    /**
     * Name of a property to set in case of an error.
     */
    private $errorProperty;

    /**
     * @var PhingUnitScriptRunner
     */
    private $scriptRunner;
    private $notifier;
    /** @var PhingUnitListener[] */
    private $listeners = [];

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->notifier = new class ($this) implements PhingUnitExecutionNotifier {
            private $outer;

            public function __construct(PhingUnit $outer)
            {
                $this->outer = $outer;
            }

            /**
             * @inheritDoc
             */
            public function fireStartTest(string $targetName)
            {
                $this->outer->fireStartTest($targetName);
            }

            /**
             * @inheritDoc
             */
            public function fireFail(string $targetName, AssertionFailedException $ae)
            {
                $this->outer->fireFail($targetName, $ae);
            }

            /**
             * @inheritDoc
             */
            public function fireError(string $targetName, \Throwable $t)
            {
                $this->outer->fireError($targetName, $t);
            }

            /**
             * @inheritDoc
             */
            public function fireEndTest(string $targetName)
            {
                $this->outer->fireEndTest($targetName);
            }
        };
    }

    /**
     * invokes start on all registered test listeners.
     * @param string $targetName the name of the target.
     */
    public function fireStartTest(string $targetName): void
    {
        foreach ($this->listeners as $pl) {
            $pl->startTest($targetName);
        }
    }

    public function fireFail(string $targetName, AssertionFailedException $ae): void
    {
        $this->failures++;
        foreach ($this->listeners as $pl) {
            $pl->addFailure($targetName, $ae);
        }
    }

    /**
     * invokes addError on all registered test listeners.
     * @param string $targetName the name of the failed target.
     * @param \Throwable $t the associated Throwable.
     */
    public function fireError(string $targetName, \Throwable $t): void
    {
        $this->errors++;
        foreach ($this->listeners as $pl) {
            $pl->addError($targetName, $t);
        }
    }

    /**
     * invokes endTest on all registered test listeners.
     * @param string $targetName the name of the current target.
     */
    public function fireEndTest(string $targetName): void
    {
        foreach ($this->listeners as $pl) {
            $pl->endTest($targetName);
        }
    }

    public function main()
    {
        if ($this->buildFiles === null) {
            throw new \BuildException(self::ERROR_NO_TESTS);
        }
        $this->doFileSet($this->buildFiles);
        if ($this->failures > 0 || $this->errors > 0) {
            if ($this->errorProperty !== null) {
                $this->getProject()->setNewProperty($this->errorProperty, 'true');
            }
            if ($this->failOnError) {
                throw new \BuildException(
                    sprintf(
                        '%s%d failure%s and %d error%s',
                        self::ERROR_TESTS_FAILED,
                        $this->failures,
                        $this->failures !== 1 ? 's' : '',
                        $this->errors,
                        $this->errors !== 1 ? 's' : ''
                    )
                );
            }
        }
    }

    /**
     * @param \FileSet $buildFiles
     */
    private function doFileSet(\FileSet $buildFiles): void
    {
        $iter = $buildFiles->getIterator();
        while ($iter->valid()) {
            /** @var \PhingFile $f */
            $f = $iter->current();
            if ($f->isFile()) {
                $this->doFile($f);
            } else {
                $this->log("Skipping {$f} since it doesn't exist", \Project::MSG_VERBOSE);
            }
            $iter->next();
        }
    }

    private function doFile(\PhingFile $f): void
    {
        $this->log("Running tests in build file {$f}", \Project::MSG_DEBUG);
        $prjFactory = new class ($f, $this) implements ProjectFactory {
            private $f;
            /** @var PhingUnit */
            private $that;

            public function __construct($f, $that)
            {
                $this->f = $f;
                $this->that = $that;
            }

            public function createProject()
            {
                return $this->that->createProjectForFile($this->f);
            }
        };
        try {
            $this->scriptRunner = new PhingUnitScriptRunner($prjFactory);
            $testTargets = $this->scriptRunner->getTestTartgets();
            $this->scriptRunner->runSuite($testTargets, $this->notifier);
        } finally {
            $this->scriptRunner = null;
        }
    }

    /**
     * Creates a new project instance and configures it.
     * @param \PhingFile $f the File for which to create a Project.
     * @return \Project
     * @throws \IOException
     * @throws \NullPointerException
     */
    public function createProjectForFile(\PhingFile $f): \Project
    {
        $p = new \Project();
        $p->init();
        $p->setInputHandler($this->getProject()->getInputHandler());

        $p->setUserProperty('phing.file', $f->getAbsolutePath());
        $p->setUserProperty('phing.dir', dirname($f->getAbsolutePath()));
        $this->attachListeners($f, $p);

        \ProjectConfigurator::configureProject($p, $f);

        return $p;
    }

    /**
     * Wraps all registered test listeners in BuildListeners and
     * attaches them to the new project instance.
     * @param \PhingFile $buildFile a build file.
     * @param \Project $p the Project to attach to.
     */
    private function attachListeners(\PhingFile $buildFile, \Project $p): void
    {
        foreach ($this->listeners as $al) {
            $p->addBuildListener(new class ($buildFile->getAbsolutePath(), $al) implements \BuildListener {
                private $buildFile;
                private $a;

                public function __construct(\PhingFile $buildFile, PhingUnitListener $a)
                {
                    $this->buildFile = $buildFile;
                    $this->a = $a;
                }

                public function buildStarted(\BuildEvent $event)
                {
                    $this->a->startTestSuite($event->getProject(), $this->buildFile);
                }

                public function buildFinished(\BuildEvent $event)
                {
                    $this->a->endTestSuite($event->getProject(), $this->buildFile);
                }

                public function targetStarted(\BuildEvent $event)
                {
                }

                public function targetFinished(\BuildEvent $event)
                {
                }

                public function taskStarted(\BuildEvent $event)
                {
                }

                public function taskFinished(\BuildEvent $event)
                {
                }

                public function messageLogged(\BuildEvent $event)
                {
                }
            });
            $al->setCurrentTestProject($p);
        }
    }
}
