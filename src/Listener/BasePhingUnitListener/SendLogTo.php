<?php

namespace Phing\PhingUnit\Listener\BasePhingUnitListener;

/**
 * Class SendLogTo
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class SendLogTo extends EnumeratedAttribute
{
    public const PHING_LOG = 'phing';
    public const FILE = 'file';
    public const BOTH = 'both';

    public function __construct(string $s = null)
    {
        $this->setValue($s);
    }

    public function getValues()
    {
        return [self::PHING_LOG, self::FILE, self::BOTH];
    }
}
    