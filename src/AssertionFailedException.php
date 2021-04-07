<?php

namespace Phing\PhingUnit;

use Phing\Exception\BuildException;

/**
 * Class AssertionFailedException
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class AssertionFailedException extends BuildException
{
    public const DEFAULT_MESSAGE = 'Test failed';
}
