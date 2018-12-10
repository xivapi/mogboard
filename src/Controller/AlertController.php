<?php

namespace App\Controller;

use App\Repository\AlertRepository;
use App\Services\User\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Users */
    private $users;

    public function __construct(EntityManagerInterface $em, Users $users)
    {
        $this->em = $em;
        $this->users = $users;
    }

    /**
     * @Route("/alerts/create", name="create_alert")
     */
    public function create(Request $request)
    {
        // get user
        $user = $this->users->getUser();

        if (!$user) {
            throw new NotFoundHttpException();
        }

        $req = json_decode($request->getContent());
        $alertItem = $req->itemId;



        return $this->render('index.html.twig');
    }
}
