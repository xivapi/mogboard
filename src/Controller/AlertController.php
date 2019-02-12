<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\AlertItem;
use App\Exceptions\InvalidAlertCreationException;
use App\Repository\AlertRepository;
use App\Service\GameData\GameServers;
use App\Service\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;

    public function __construct(EntityManagerInterface $em, Users $users)
    {
        $this->em = $em;
        $this->users = $users;
    }

    /**
     * @Route("/alerts/create", name="create_alert")
     */
    public function create(Request $request)
    {
        // get user
        $user = $this->users->getUser(true);

        // get alert payload
        $payload = json_decode($request->getContent());
        
        //
        // todo - validate
        //  - The request
        //  - The item id
        //

        $alert = new Alert();
        $alert
            ->setUser($user)
            ->setItemId($payload->itemId)
            ->setName($payload->name)
            ->setTriggerOption($payload->option)
            ->setTriggerValue($payload->value)
            ->setTriggerDelay($user->isPatron() ? Alert::DELAY_PATRON : Alert::DELAY_DEFAULT)
            ->setTriggerHq($payload->hq)
            ->setTriggerNq($payload->nq)
            ->setNotifiedViaDiscord($payload->discord)
            ->setNotifiedViaEmail($payload->email);
            
        $this->em->persist($alert);
        $this->em->flush();
        
        return $this->json([
            'ok' => true,
        ]);
    }
    
    /**
     * @Route("/alerts/get/{itemId}", name="alert_get")
     */
    public function fetch($itemId)
    {
        // get alerts for the current user
        $alerts = $this->em->getRepository(Alert::class)->findBy([
            'itemId' => $itemId,
            'user'   => $this->users->getUser(true),
        ]);
        
        return $this->render('Product/alerts_table.html.twig', [
            'alerts' => $alerts
        ]);
    }
}
