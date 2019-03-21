<?php

namespace App\Controller;

use App\Entity\UserCharacter;
use App\Service\UserCharacters\UserCharacters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserCharacterController extends AbstractController
{
    /** @var UserCharacters */
    private $characters;

    public function __construct(UserCharacters $characters)
    {
        $this->characters = $characters;
    }

    /**
     * Add a users character to the site, finding the ID should be
     * done via JS and XIVAPI search before it hits this endpoint
     *
     * submit:
     * - lodestone_id
     *
     * @Route("/characters/add", name="characters_add")
     */
    public function add(Request $request)
    {
        $this->characters->add($request);
        return $this->json(true);
    }

    /**
     * @Route("/characters/{lodestoneId}/confirm", name="characters_confirm")
     */
    public function confirm(int $lodestoneId)
    {
        $this->characters->confirm($lodestoneId);
        return $this->json(true);
    }
}
