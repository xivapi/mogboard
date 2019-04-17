<?php

namespace App\Controller;

use App\Entity\UserRetainer;
use App\Service\UserRetainers\UserRetainers;
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
}
