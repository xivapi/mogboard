<?php

namespace App\Controller;

use App\Common\Entity\UserCharacter;
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
    
    /**
     * @Route("/characters/{character}/delete", name="characters_delete")
     */
    public function delete(UserCharacter $character)
    {
        $this->characters->delete($character);
        return $this->redirectToRoute('user_account_characters');
    }
}
