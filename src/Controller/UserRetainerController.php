<?php

namespace App\Controller;

use App\Common\Entity\UserRetainer;
use App\Common\Exceptions\BasicException;
use App\Common\User\Users;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
     * @Route("/retainers/{retainer}/shop", name="retainer_shop")
     */
    public function store(Request $request, UserRetainer $retainer)
    {
        $user = $this->users->getUser(true);
        $this->users->setLastUrl($request);

        // verify the user owns this retainer
        if ($retainer->getUser() !== $user && !$user->isAdmin()) {
            throw new UnauthorizedHttpException('Denied', 'Sorry, you do not own this retainer and will not be able to view its shop.');
        }

        $items = $this->retainers->getStore($retainer);

        if ($items == null) {
            return $this->redirectToRoute('404');
        }

        $items = $items->Items;
        $marketStats = null;

        if ($items) {
            $marketStats = $this->retainers->getMarketData($items);
        }

        // get the retainer store for this user
        return $this->render('UserRetainers/store.html.twig', [
            'retainer'     => $retainer,
            'store'        => $items,
            'market_stats' => $marketStats,
        ]);
    }
}
