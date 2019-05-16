<?php

namespace App\Controller;

use App\Common\Entity\UserRetainer;
use App\Common\User\Users;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserRetainerController extends AbstractController
{
    /** @var Users */
    private $users;
    /** @var UserRetainers */
    private $retainers;
    
    public function __construct(Users $users, UserRetainers $retainers)
    {
        $this->users     = $users;
        $this->retainers = $retainers;
    }

    /**
     * Add a users character to the site, finding the ID should be
     * done via JS and XIVAPI search before it hits this endpoint
     *
     * submit:
     * - name, server
     *
     * @Route("/retainers/add", name="retainers_add")
     */
    public function add(Request $request)
    {
        return $this->json(
            $this->retainers->add($request)
        );
    }

    /**
     * @Route("/retainers/{retainer}/confirm", name="retainers_confirm")
     */
    public function confirm(UserRetainer $retainer)
    {
        return $this->json(
            $this->retainers->confirm($retainer)
        );
    }
    
    /**
     * @Route("/retainers/{retainer}/delete", name="retainers_delete")
     */
    public function delete(UserRetainer $retainer)
    {
        $this->retainers->delete($retainer);
        return $this->redirectToRoute('user_account_retainers');
    }
    
    /**
     * @Route("/retainers/{retainerId}", name="retainer_store")
     */
    public function store(Request $request, string $retainerId)
    {
        $user = $this->users->getUser();
        $this->users->setLastUrl($request);

        // you must be online to view stores
        if ($user === null) {
            return $this->redirectToRoute('user_account');
        }

        // get the retainer store for this user
        return $this->render('UserRetainers/store.html.twig', [
            'retainer_store' => $this->retainers->getStore($retainerId),
        ]);
    }
}
