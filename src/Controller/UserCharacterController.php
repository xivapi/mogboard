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
     * @Route("/characters/{lodestoneId}/add", name="characters_add")
     */
    public function confirm(int $lodestoneId)
    {
        return $this->json(
            $this->characters->confirm($lodestoneId)
        );
    }
}
