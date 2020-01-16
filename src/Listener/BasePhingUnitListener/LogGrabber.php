<?php

namespace Phing\PhingUnit\Listener\BasePhingUnitListener;

use Phing\PhingUnit\Listener\BasePhingUnitListener;

class LogGrabber implements \BuildListener
{
    /** @var BasePhingUnitListener */
    private $basePhingUnitListener;

    public function __construct(BasePhingUnitListener $listener)
    {
        $this->basePhingUnitListener = $listener;
    }
    
    public function buildStarted(\BuildEvent $event)
    {
    }

    public function buildFinished(\BuildEvent $event)
    {
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
        $priority = $event->getPriority();
        // Filter out messages based on priority
        if ($priority <= $this->basePhingUnitListener->getLogLevel()->getLevel()) {
            $this->basePhingUnitListener->messageLogged($event);
        }
    }
}