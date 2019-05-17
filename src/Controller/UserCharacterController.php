<?php

namespace App\Controller;

use App\Common\Entity\UserCharacter;
use App\Common\Exceptions\BasicException;
use App\Common\User\Users;
use App\Service\UserCharacters\UserCharacters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UserCharacterController extends AbstractController
{
    /** @var Users */
    private $users;
    /** @var UserCharacters */
    private $characters;

    public function __construct(Users $users, UserCharacters $characters)
    {
        $this->users      = $users;
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

    /**
     * @Route("/characters/{character}/history", name="character_history")
     */
    public function history(Request $request, UserCharacter $character)
    {
        $user = $this->users->getUser(true);

        // verify the user owns this character
        if ($character->getUser() !== $user) {
            throw new BasicException('Sorry, you do not own this character and will not be able to view its history.');
        }

        if ($character->getLodestoneId() === null) {
            throw new BasicException('Sorry, this character has not yet synced with XIVAPI and no data can be linked up, try again soon.');
        }

        $this->users->setLastUrl($request);

        $history = $this->characters->getHistory($character);

        if ($history == null) {
            return $this->redirectToRoute('404');
        }

        // get the retainer store for this user
        return $this->render('UserCharacter/history.html.twig', [
            'character' => $character,
            'history'   => $history,
        ]);
    }
}
