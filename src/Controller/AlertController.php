<?php

namespace App\Controller;

use App\Entity\UserAlert;
use App\Service\Alert\Alerts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Alerts */
    private $alerts;

    public function __construct(EntityManagerInterface $em, Alerts $alerts)
    {
        $this->em = $em;
        $this->alerts = $alerts;
    }

    /**
     * @Route("/alerts/create", name="alerts_create")
     */
    public function create(Request $request)
    {
        return $this->alerts->save(
            UserAlert::buildFromRequest($request)
        );
    }

    /**
     * @Route("/alerts/{alert}/update", name="alerts_update")
     */
    public function update(Request $request, UserAlert $alert)
    {
        return $this->alerts->save(
            UserAlert::buildFromRequest($request, $alert)
        );
    }

    /**
     * @Route("/alerts/create", name="alerts_create")
     */
    public function delete(Request $request)
    {
        return $this->alerts->delete(
            $request->get('id')
        );
    }

    /**
     * @Route("/alerts/get/{itemId}", name="alert_get")
     */
    public function fetch($itemId)
    {
        // get alerts for the current user
        $alerts = $this->em->getRepository(UserAlert::class)->findBy([
            'itemId' => $itemId,
            'user'   => $this->users->getUser(true),
        ]);
        
        return $this->render('Product/alerts_table.html.twig', [
            'alerts' => $alerts
        ]);
    }
}
