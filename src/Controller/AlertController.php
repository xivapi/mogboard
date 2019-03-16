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
     * @Route("/alerts/{alert}/delete", name="alerts_delete")
     */
    public function delete(UserAlert $alert)
    {
        return $this->alerts->delete($alert);
    }

    /**
     * @Route("/alerts/render/item/{itemId}", name="alerts_render_item")
     */
    public function renderAlertsForItem($itemId)
    {
        return $this->render('Product/alerts_table.html.twig', [
            'alerts' => $this->alerts->getAllForItemForCurrentUser($itemId)
        ]);
    }
}
