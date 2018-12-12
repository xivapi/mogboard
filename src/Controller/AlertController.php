<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Entity\AlertItem;
use App\Exceptions\InvalidAlertCreationException;
use App\Repository\AlertRepository;
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
        
        if (empty($request->get('data'))) {
            throw new InvalidAlertCreationException();
        }
        
        // data
        [
            $itemId,
            $server,
            $name,
            $condition,
            $conditionValue,
            $conditionHQ,
            $viaDesktop,
            $viaDiscord,
            $viaEmail,
            $limit,
            $delay
        ] = explode(',', $request->get('data'));
        
        // look for an alert item, if non make one
        $alertItem = $this->em->getRepository(AlertItem::class)->findOneBy([
            'itemId' => $itemId,
            'server' => $server
        ]);
        
        // create alert item if one does not exist
        if (!$alertItem) {
            $alertItem = new AlertItem();
            $alertItem
                ->setItemId($itemId)
                ->setServer($server);
        }

        $alert = new Alert();
        $alert
            ->setUser($user)
            ->setAlertItem($alertItem)
            ->setName($name)
            ->setTriggerOption($condition)
            ->setTriggerValue($conditionValue)
            ->setTriggerLimit($limit)
            ->setTriggerDelay($delay)
            ->setTriggerHq($conditionHQ)
            ->setNotifiedViaDesktop($viaDesktop)
            ->setNotifiedViaDiscord($viaDiscord)
            ->setNotifiedViaEmail($viaEmail);
            
        $this->em->persist($alertItem);
        $this->em->persist($alert);
        $this->em->flush();
        
        return $this->json([
            'ok'    => true,
            'alert' => $alert
        ]);
    }
}
