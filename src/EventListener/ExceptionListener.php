<?php

namespace App\EventListener;

use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $ex         = $event->getException();
        $path       = $event->getRequest()->getPathInfo();
        $pathinfo   = pathinfo($path);
    
        if (isset($pathinfo['extension']) && strlen($pathinfo['extension'] > 2)) {
            $event->setResponse(new Response("File not found: ". $path, 404));
            return null;
        }

        $file = str_ireplace('/home/dalamud/dalamud', '', $ex->getFile());
        $file = str_ireplace('/home/dalamud/dalamud_staging', '', $file);
        $message = $ex->getMessage() ?: '(no-exception-message)';

        $json = [
            'Error'   => true,
            'Subject' => 'MOGBOARD Service Error',
            'Message' => $message,
            'Hash'    => sha1($message),
            'Debug'   => [
                'Env'     => getenv('APP_ENV'),
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'Date'    => date('Y-m-d H:i:s'),
            ]
        ];

        $response = new JsonResponse($json, $json->Debug->Code);
        $response->headers->set('Content-Type','application/json');
        $event->setResponse($response);
    }
}
