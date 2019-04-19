<?php

namespace App\EventListener;

use App\Service\Common\Environment;
use App\Service\Common\Language;
use App\Service\User\Users;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /** @var Users */
    private $users;

    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($sentry = getenv('SENTRY_KEY')) {
            (new \Raven_Client($sentry))->install();
        }
    
        /** @var Request $request */
        $request = $event->getRequest();
    
        // register environment
        Environment::register($request);
    
        // register language based on domain
        Language::register($request);

        // refresh alert expiry dates
        $this->users->refreshUsersAlerts();
    }
}
