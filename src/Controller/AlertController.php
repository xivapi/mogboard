<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\AlertItem;
use App\Exceptions\InvalidAlertCreationException;
use App\Repository\AlertRepository;
use App\Services\GameData\GameServers;
use App\Services\User\Users;
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
        $user = $this->users->getUser();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        // grab users server
        $server = GameServers::getServer();

        // get alert payload
        $payload = json_decode($request->getContent());
        
        //
        // todo - validate
        //  - The request
        //  - The item id
        //

        // look for an alert item, if non make one
        $alertItem = $this->em->getRepository(AlertItem::class)->findOneBy([
            'itemId' => $payload->itemId,
            'server' => $server
        ]);
        
        // create alert item if one does not exist
        if (!$alertItem) {
            $alertItem = new AlertItem($payload->itemId, $server);
        }

        $alert = new Alert();
        $alert
            ->setUser($user)
            ->setAlertItem($alertItem)
            ->setName($payload->name)
            ->setTriggerOption($payload->option)
            ->setTriggerValue($payload->value)
            ->setTriggerDelay($user->isPatron() ? Alert::DELAY_PATRON : Alert::DELAY_DEFAULT)
            ->setTriggerHq($payload->hq)
            ->setTriggerNq($payload->nq)
            ->setNotifiedViaDiscord($payload->discord)
            ->setNotifiedViaEmail($payload->email);
            
        $this->em->persist($alertItem);
        $this->em->persist($alert);
        $this->em->flush();
        
        return $this->json([
            'ok' => true,
        ]);
    }
}
