<?php

namespace Phing\PhingUnit;

/**
 * Class AssertionFailedException
 * @package Phing\PhingUnit
 */
class AssertionFailedException extends \BuildException
{
    public const DEFAULT_MESSAGE = 'Test failed';
}
