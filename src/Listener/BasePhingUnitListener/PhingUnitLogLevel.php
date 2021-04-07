<?php

namespace Phing\PhingUnit\Listener\BasePhingUnitListener;

use Phing\Project;

/**
 * Class PhingUnitLogLevel
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class PhingUnitLogLevel extends EnumeratedAttribute
{
    public const NONE = 'none';

    private const levels = [
        Project::MSG_ERR - 1,
        Project::MSG_ERR,
        Project::MSG_WARN,
        Project::MSG_WARN,
        Project::MSG_INFO,
        Project::MSG_VERBOSE,
        Project::MSG_DEBUG
    ];

    private function __construct(string $value)
    {
        parent::__construct();
        $this->setValue($value);
    }

    public function getValues()
    {
        return [
            "none",
            "error",
            "warn",
            "warning",
            "info",
            "verbose",
            "debug"
        ];
    }

    public function getLevel()
    {
        return self::levels[$this->getIndex()];
    }
}
    