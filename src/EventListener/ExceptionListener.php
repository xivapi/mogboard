<?php

namespace App\EventListener;

use App\Exceptions\GeneralJsonException;
use App\Service\Redis\Redis;
use App\Service\ThirdParty\Discord\Discord;
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

        $json = (Object)[
            'Error'   => true,
            'Subject' => 'MOGBOARD Service Error',
            'Message' => $message,
            'Hash'    => sha1($message),
            'Debug'   => (Object)[
                'Env'     => getenv('APP_ENV'),
                'File'    => "#{$ex->getLine()} {$file}",
                'Method'  => $event->getRequest()->getMethod(),
                'Path'    => $event->getRequest()->getPathInfo(),
                'Action'  => $event->getRequest()->attributes->get('_controller'),
                'Code'    => method_exists($ex, 'getStatusCode') ? $ex->getStatusCode() : 500,
                'Date'    => date('Y-m-d H:i:s'),
            ]
        ];
        
        $ignore = false;
        
        // ignore 404 errors
        if ($json->Debug->Code == '404') {
            $ignore = true;
        }
        
        // ignore local
        if (stripos($json->Debug->File, 'vagrant') !== false) {
            $ignore = true;
        }
        
        if ($ignore === false && Redis::Cache()->get("mb_error_{$json->Hash}") == null) {
            Redis::Cache()->set("mb_error_{$json->Hash}", true);
            Discord::mog()->sendMessage(
                '569968196455759907',
                "```". json_encode($json, JSON_PRETTY_PRINT) ."```"
            );
        }
    
        // ignore non JSON errors
        if (get_class($ex) !== GeneralJsonException::class) {
            return;
        }

        $response = new JsonResponse($json, $json->Debug->Code);
        $response->headers->set('Content-Type','application/json');
        $event->setResponse($response);
    }
}
