<?php

namespace App\EventListener;

use App\Common\Constants\DiscordConstants;
use App\Common\Exceptions\BasicException;
use App\Common\Service\Redis\Redis;
use App\Common\ServicesThirdParty\Discord\Discord;
use App\Common\Utils\Environment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment as TwigEnvironment;

class ExceptionListener implements EventSubscriberInterface
{
    /** @var TwigEnvironment */
    private $twig;

    /**
     * TestTwig constructor.
     */
    public function __construct(TwigEnvironment $twig)
    {
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    /**
     * Handle custom exceptions
     * @param GetResponseForExceptionEvent $event
     * @return null|void
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /**
         * If we're in dev mode, show the full error
         */
        if (getenv('APP_ENV') == 'dev') {
            return null;
        }

        /**
         * Make sure it isn't an image or some kind of file
         */
        $pi = pathinfo(
            $event->getRequest()->getPathInfo()
        );

        if (isset($pi['extension']) && strlen($pi['extension'] > 2)) {
            $event->setResponse(new Response("File not found, sorry. Try harder.", 404));
            return null;
        }

        /**
         * Handle error info
         */
        $ex    = $event->getException();
        $error = (Object)[
            'message'       => $ex->getMessage() ?: '(no-exception-message)',
            'code'          => $ex->getCode() ?: 200,
            'ex_class'      => get_class($ex),
            'ex_file'       => $ex->getFile(),
            'ex_line'       => $ex->getLine(),
            'req_uri'       => $event->getRequest()->getUri(),
            'req_method'    => $event->getRequest()->getMethod(),
            'req_path'      => $event->getRequest()->getPathInfo(),
            'req_action'    => $event->getRequest()->attributes->get('_controller'),
            'env'           => constant(Environment::CONSTANT),
            'date'          => date('Y-m-d H:i:s'),
            'hash'          => sha1($ex->getMessage() . $ex->getFile()),
        ];

        /**
         * Send error to discord if not sent within the hour AND the exception is not a valid one.
         */
        $validExceptions = [
            BasicException::class,
            NotFoundHttpException::class
        ];

        if (Redis::Cache()->get(__METHOD__ . $error->hash) == null && !in_array($error->ex_class, $validExceptions)) {
            Redis::Cache()->set(__METHOD__ . $error->hash, true);
            Discord::mog()->sendMessage(
                DiscordConstants::ROOM_ERRORS,
                "```". json_encode($error, JSON_PRETTY_PRINT) ."```"
            );
        }

        /**
         * Render error to the user
         */
        $html = $this->twig->render('Errors/error.html.twig', [ 'error' => $error ]);
        $response = new Response($html, $error->code);
        $event->setResponse($response);
    }
}
