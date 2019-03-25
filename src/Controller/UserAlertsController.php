<?php

namespace App\Controller;

use App\Entity\UserAlert;
use App\Service\UserAlerts\UserAlerts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserAlertsController extends AbstractController
{
    /** @var UserAlerts */
    private $alerts;

    public function __construct(UserAlerts $alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * @Route("/alerts/form", name="alerts_form_new")
     * @Route("/alerts/form/{userAlert}", name="alerts_form_edit")
     */
    public function form(Request $request, ?UserAlert $userAlert = null)
    {
        $form = $this->createForm(UserAlert::class, $userAlert);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            return $this->redirectToRoute('task_success');
        }

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/alerts/create", name="alerts_create")
     */
    public function create(Request $request)
    {
        return $this->json(
            $this->alerts->save(
                UserAlert::buildFromRequest($request)
            )
        );
    }

    /**
     * @Route("/alerts/{alert}/update", name="alerts_update")
     */
    public function update(Request $request, UserAlert $alert)
    {
        return $this->json(
            $this->alerts->save(
                UserAlert::buildFromRequest($request, $alert)
            )
        );
    }

    /**
     * @Route("/alerts/{alert}/delete", name="alerts_delete")
     */
    public function delete(UserAlert $alert)
    {
        return $this->json(
            $this->alerts->delete($alert)
        );
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
