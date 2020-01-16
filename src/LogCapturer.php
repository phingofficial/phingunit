<?php

namespace Phing\PhingUnit;

/**
 * Captures log messages generated during an phingunit task run and
 * makes them available to tasks via a project reference.
 *
 * This class captures all messages generated during the build and
 * adds itself as project reference to the project.
 * @package Phing\PhingUnit
 */
class LogCapturer implements \BuildListener
{
    public const REFERENCE_ID = 'phing.phingunit.log';

    private $events = [];
    
    /** @var \Project $p */
    private $p;

    public function __construct(\Project $p)
    {
        $this->p = $p;
        $p->addBuildListener($this);
        $p->addReference(self::REFERENCE_ID, $this);
    }

    /**
     * All messages with `$this->logLevel === \Project::MSG_ERR`.
     * @param bool $mergeLines whether to merge messages into a single line
     * or split them into multiple lines
     * @return string All messages with `$this->logLevel === \Project::MSG_ERR`
     */
    public function getErrLog(bool $mergeLines = true): string
    {
        return $this->getLog(\Project::MSG_ERR, $mergeLines);
    }

    private function getLog(int $minPriority, bool $mergeLines): string
    {
        $sb = '';
        foreach ($this->events as $it) {
            self::append($sb, $it, $minPriority, $mergeLines);
        }
        return $sb;
    }

    private static function append(
        &$sb,
        \BuildEvent $event,
        int $minPriority,
        bool $mergeLines
    ): void {
        if ($event->getPriority() <= $minPriority) {
            $sb .= $event->getMessage();
            if (!$mergeLines) {
                $sb .= PHP_EOL;
            }
        }
    }

    /**
     * All messages with `$this->logLevel === \Project::MSG_WARN` or
     * more severe.
     * @param bool $mergeLines whether to merge messages into a single line
     * or split them into multiple lines
     * @return string All messages with `$this->logLevel === \Project::MSG_WARN` or above
     */
    public function getWarnLog(bool $mergeLines): string
    {
        return $this->getLog(\Project::MSG_WARN, $mergeLines);
    }

    /**
     * All messages with `$this->logLevel === \Project::MSG_INFO` or
     * more severe.
     * @param bool $mergeLines whether to merge messages into a single line
     * or split them into multiple lines
     * @return string All messages with `$this->logLevel === \Project::MSG_INFO` or above
     */
    public function getInfoLog(bool $mergeLines): string
    {
        return $this->getLog(\Project::MSG_INFO, $mergeLines);
    }

    /**
     * All messages with `$this->logLevel === \Project::MSG_VERBOSE` or
     * more severe.
     * @param bool $mergeLines whether to merge messages into a single line
     * or split them into multiple lines
     * @return string All messages with `$this->logLevel === \Project::MSG_VERBOSE` or above
     */
    public function getVerboseLog(bool $mergeLines): string
    {
        return $this->getLog(\Project::MSG_VERBOSE, $mergeLines);
    }

    /**
     * All messages with `$this->logLevel === \Project::MSG_DEBUG` or
     * more severe.
     * @param bool $mergeLines whether to merge messages into a single line
     * or split them into multiple lines
     * @return string All messages with `$this->logLevel === \Project::MSG_DEBUG` or above
     */
    public function getDebugLog(bool $mergeLines): string
    {
        return $this->getLog(\Project::MSG_DEBUG, $mergeLines);
    }

    /**
     * Empty.
     * @param \BuildEvent $event
     */
    public function buildStarted(\BuildEvent $event)
    {
    }

    /**
     * Empty.
     * @param \BuildEvent $event
     */
    public function targetStarted(\BuildEvent $event)
    {
    }

    /**
     * Empty.
     * @param \BuildEvent $event
     */
    public function targetFinished(\BuildEvent $event)
    {
    }

    /**
     * Empty.
     * @param \BuildEvent $event
     */
    public function taskStarted(\BuildEvent $event)
    {
    }

    /**
     * Empty.
     * @param \BuildEvent $event
     */
    public function taskFinished(\BuildEvent $event)
    {
    }

    /**
     * De-register.
     * @param \BuildEvent $event
     */
    public function buildFinished(\BuildEvent $event)
    {
        if ($this->p !== null && $event->getProject() === $this->p) {
            $this->p->removeBuildListener($this);
            // $this->p->getReferences()->remove(self::REFERENCE_ID);
            $this->p = null;
        }
    }

    /**
     * Record the message.
     * @param \BuildEvent $event
     */
    public function messageLogged(\BuildEvent $event)
    {
        $this->events[] = $event;
    }
}