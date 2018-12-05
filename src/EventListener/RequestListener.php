<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($sentry = getenv('SENTRY_KEY')) {
            (new \Raven_Client($sentry))->install();
        }
    }
}
