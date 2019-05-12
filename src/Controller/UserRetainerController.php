<?php

namespace App\Controller;

use App\Common\Entity\UserRetainer;
use App\Service\UserRetainers\UserRetainers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use XIVAPI\XIVAPI;

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
    
    /**
     * @Route("/retainers/{retainer}/toggle-privacy", name="retainers_toggle_privacy")
     */
    public function togglePrivacy(UserRetainer $retainer)
    {
        $this->retainers->togglePrivacy($retainer);
        return $this->redirectToRoute('user_account_retainers');
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
     * @Route("/retainers/{slug}", name="retainer_store")
     */
    public function store(string $slug)
    {
        $isStoreSlug = substr($slug, 0, 3) == 'mb-';
    
        /** @var UserRetainer $retainer */
        $retainer = $isStoreSlug
            ? $this->retainers->getSlugRetainer($slug)
            : $this->retainers->getCompanionApiRetainer($slug);
        
        if ($isStoreSlug && $retainer === null) {
            return $this->redirectToRoute('404');
        }
        
        /**
         * Grab the retainer from the API
         */
        try {
            $apiRetainer  = (new XIVAPI())->market->retainer(
                ($retainer && $retainer->hasApiRetainerId()) ? $retainer->getApiRetainerId() : $slug
            );
        } catch (\Exception $ex) {
            $apiRetainer = null;
        }
        
        if ($retainer === null && $apiRetainer === null) {
            return $this->redirectToRoute('404');
        }
        
        // if no retainer, find name from 1st item and make a temp user retainer object
        if ($retainer === null) {
            $retainer = new UserRetainer();
            $retainer->setName($apiRetainer->Name)->setServer($apiRetainer->Server);
        }
        
        return $this->render('UserRetainers/index.html.twig', [
            'retainer'    => $retainer,
            'apiRetainer' => $apiRetainer
        ]);
    }
}
