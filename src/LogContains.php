<?php

namespace Phing\PhingUnit;

use Phing\Exception\BuildException;
use Phing\Project;
use Phing\ProjectComponent;
use Phing\Task\System\Condition\Condition;
use Phing\Task\System\Element\LogLevelAware;

/**
 * A condition that tests the log output of the current project for a
 * given string.
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class LogContains extends ProjectComponent implements Condition
{
    use LogLevelAware;

    /** @var string */
    private $text;

    /** @var bool */
    private $mergeLines = true;

    public function __construct()
    {
        $this->logLevel = Project::MSG_INFO;
        $this->logLevelName = 'info';
        parent::__construct();
    }
    
    /**
     * Test the log shall contain.
     * @param string $t text to look for
     */
    public function setText(string $t)
    {
        $this->text = $t;
    }

    /**
     * Whether to merge messages into a single line or split them into
     * multiple lines.
     * @param bool $b whether to merge messages into a single line
     */
    public function setMergeLines(bool $b)
    {
        $this->mergeLines = $b;
    }

    public function evaluate()
    {
        if ($this->text === null) {
            throw new BuildException('the text attribute is required');
        }
        $o = $this->getProject()->getReference(LogCapturer::REFERENCE_ID);
        if ($o instanceof LogCapturer) {
            $c = $o;
            switch ($this->logLevel) {
                case Project::MSG_ERR:
                    $log = $c->getErrLog($this->mergeLines);
                    break;
                case Project::MSG_WARN:
                    $log = $c->getWarnLog($this->mergeLines);
                    break;
                case Project::MSG_INFO:
                    $log = $c->getInfoLog($this->mergeLines);
                    break;
                case Project::MSG_VERBOSE:
                    $log = $c->getVerboseLog($this->mergeLines);
                    break;
                case Project::MSG_DEBUG:
                    $log = $c->getDebugLog($this->mergeLines);
                    break;

                default:
                    throw new BuildException('Unknown logLevel: ' . $this->logLevelName);
            }

            return strpos($log, $this->text) !== false;
        }

        return false;
    }
}
