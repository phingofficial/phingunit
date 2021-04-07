<?php

namespace Phing\PhingUnit\Listener;

use Phing\Exception\BuildException;
use Phing\Io\File;
use Phing\Io\FileOutputStream;
use Phing\Io\FileUtils;
use Phing\Io\IOException;
use Phing\Io\OutputStream;
use Phing\Listener\BuildEvent;
use Phing\Parser\Location;
use Phing\Phing;
use Phing\PhingUnit\AssertionFailedException;
use Phing\PhingUnit\Listener\BasePhingUnitListener\LogGrabber;
use Phing\PhingUnit\Listener\BasePhingUnitListener\PhingUnitLogLevel;
use Phing\PhingUnit\Listener\BasePhingUnitListener\SendLogTo;
use Phing\PhingUnit\PhingUnitListener;
use Phing\Project;
use Phing\Task;
use Phing\Util\StringHelper;

/**
 * Class BasePhingUnitListener
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
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
    
    /** @var Task */
    private $parentTask;
    /** @var Project */
    private $currentTest;
    /**
     * The minimum level a log message must be logged at to be
     * included in the output.
     */
    private $logLevel;

    public function setParentTask(Task $t)
    {
        $this->parentTask = $t;
    }

    public function startTestSuite(Project $testProject, string $buildFile)
    {
        $this->start = microtime(true);
        $this->runCount = $this->failureCount = $this->errorCount = 0;
    }

    public function startTest(string $target)
    {
        $this->testStart = microtime(true);
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

    public function setCurrentTestProject(Project $p)
    {
        $this->currentTest = $p;
        $p->addBuildListener(new LogGrabber($this));
    }

    /**
     * Sets the minimum level a log message must be logged at to be
     * included in the output.
     * @param PhingUnitLogLevel $l minimum level
     */
    public function setLogLevel(PhingUnitLogLevel $l): void
    {
        $this->logLevel = $l;
    }

    /**
     * @return mixed
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Directory to write reports to.
     * @return File $directory to write reports to
     */
    protected function getToDir()
    {
        return $this->toDir;
    }

    /**
     * Sets the directory to write test reports to.
     * @param File $f directory to write reports to
     */
    public function setToDir(File $f): void
    {
        $this->toDir = $f;
    }

    /**
     * Where to send the test report.
     * @param SendLogTo $logTo where to send the test report
     */
    protected function setSendLogTo(SendLogTo $logTo): void
    {
        $this->logTo = $logTo;
    }

    /**
     * @param OutputStream $out
     * @throws IOException
     */
    protected function close(OutputStream $out): void
    {
        $out->close();
    }

    /**
     * @param string $buildFile
     * @return OutputStream
     * @throws IOException
     */
    protected function getOut(string $buildFile)
    {
        $l = $f = null;
        if ($this->logTo->getValue() === SendLogTo::PHING_LOG || $this->logTo->getValue() === SendLogTo::BOTH) {
            if ($this->parentTask !== null) {
                $l = new LogOutputStream($this->parentTask, Project::MSG_INFO);
            } else {
                $l = Phing::getOutputStream();
            }
            if ($this->logTo->getValue() === SendLogTo::PHING_LOG) {
                return $l;
            }
        }
        if ($this->logTo->getValue() === SendLogTo::FILE || $this->logTo->getValue() === SendLogTo::BOTH) {
            $fileName = "TEST-" . $this->normalize($buildFile) . "." . $this->extension;
            $noDir = $this->parentTask !== null
                ? $this->parentTask->getProject()->resolveFile($fileName)
                : new File($fileName);
            $file = $this->toDir === null ? $noDir : new File($this->toDir, $fileName);
            try {
                $f = new FileOutputStream($file);
            } catch (\Exception $e) {
                throw new BuildException($e);
            }
            if ($this->logTo->getValue() === SendLogTo::FILE) {
                return $f;
            }
        }
        return new TeeOutputStream($l, $f);
    }

    /**
     * Turns the build file name into something that vaguely looks
     * like a Java classname.  Close enough to be suitable for
     * junitreport.
     * @param string $buildFile the test file name
     * @return string the normalized name
     * @throws IOException
     */
    protected function normalize(string $buildFile): string
    {
        $base = $this->parentTask !== null
        ? $this->parentTask->getProject()->getBaseDir()
        : new File(Phing::getProperty('user.dir'));
        $buildFile = (new File($buildFile))->getPathWithoutBase($base);
        if ($buildFile !== '' && $buildFile[0] === FileUtils::getSeparator()) {
            $buildFile = StringHelper::substring($buildFile, 1);
        }

        return str_replace(['.', ':', FileUtils::getSeparator()], ['_', '_', '.'], $buildFile);
    }

    protected function getLocation(\Throwable $t): Location
    {
        $l = new Location();
        if ($t instanceof BuildException) {
            $l2 = $t->getLocation();
            if ($l2 !== null) {
                $l = $l2;
            }
        }
        return $l;
    }

    protected function getCurrentTestProject(): Project
    {
        return $this->currentTest;
    }

    /**
     * Gets messages from the project running the test target if their
     * level is at least of the level specified with {@link
     * #setLogLevel setLogLevel}.
     *
     * <p>This implementation is empty.</p>
     * @param BuildEvent $event the logged message
     */
    public function messageLogged(BuildEvent $event)
    {
    }
}
