<?php

namespace Phing\PhingUnit;

use Phing\Project;

/**
 * Interface ProjectFactory
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
interface ProjectFactory
{
    public function createProject(): Project;
}
