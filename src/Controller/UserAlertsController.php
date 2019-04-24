<?php

namespace App\Controller;

use App\Entity\UserAlert;
use App\Service\ThirdParty\Discord\Discord;
use App\Service\User\Users;
use App\Service\UserAlerts\UserAlerts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserAlertsController extends AbstractController
{
    /** @var Users */
    private $users;
    /** @var UserAlerts */
    private $alerts;

    public function __construct(UserAlerts $alerts, Users $users)
    {
        $this->alerts = $alerts;
        $this->users  = $users;
    }

    /**
     * @Route("/alerts/create", name="alerts_create")
     */
    public function create(Request $request)
    {
        try {
            $user = $this->users->getUser(true);
            $totalAlerts = count($user->getAlerts());
    
            if ($totalAlerts >= $user->getAlertsMax()) {
                return $this->json([
                    false,
                    "Could not create alert, you seem to be maxed out!? You can make a max of: {$user->getAlertsMax()} - You currently have: {$totalAlerts}"
                ]);
            }
    
            $this->alerts->save(
                UserAlert::buildFromRequest($request)
            );
        } catch (\Exception $ex) {
            $hash = substr(sha1(microtime(true)), 0, 8);
            Discord::mog()->sendMessage(
                '569968196455759907',
                "``({$hash}) Could not create alert for user: {$user->getUsername()}, reason: {$ex->getMessage()}```"
            );
            
            return [
                false,
                "Alert could not be created due to internal error, please inform a site admin with the error code: {$hash}"
            ];
        }

        return $this->json([
            true,
            'Alert has been created!',
        ]);
    }

    /**
     * @Route("/alerts/{alert}/update", name="alerts_update")
     */
    public function update(Request $request, UserAlert $alert)
    {
        return $this->json(
            $this->alerts->save(
                UserAlert::buildFromRequest($request, $alert),
                false
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
     * @Route("/alerts/{alert}/edit", name="alerts_edit")
     */
    public function fetchAlertJsonForEditing(UserAlert $alert)
    {
        return $this->json([
            'id'                   => $alert->getId(),
            'alert_name'           => $alert->getName(),
            'alert_nq'             => $alert->isTriggerNq(),
            'alert_hq'             => $alert->isTriggerHq(),
            'alert_dc'             => $alert->isTriggerDataCenter(),
            'alert_notify_discord' => $alert->isNotifiedViaDiscord(),
            'alert_notify_email'   => $alert->isNotifiedViaEmail(),
            'triggers'             => $alert->getTriggerConditionsFormatted(),
        ]);
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
