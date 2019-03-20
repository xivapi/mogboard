<?php

namespace App\Controller;

use App\Entity\UserRetainer;
use App\Service\UserLists\UserRetainers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserRetainerController extends AbstractController
{
    /** @var UserRetainers */
    private $retainers;
    
    public function __construct(UserRetainers $retainers)
    {
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
        $this->retainers->add($request);
        return $this->json(true);
    }

    /**
     * @Route("/retainers/{retainer}/confirm", name="retainers_confirm")
     */
    public function confirm(UserRetainer $retainer)
    {
        $this->retainers->confirm($retainer);
        return $this->json(true);
    }
}
