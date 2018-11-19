<?php

namespace App\Twig;

use App\Services\User\Users;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UserExtension extends AbstractExtension
{
    /** @var Users */
    private $users;
    
    public function __construct(Users $users)
    {
        $this->users = $users;
    }
    
    public function getFilters()
    {
        return [];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('user', [$this, 'getUser']),
        ];
    }
    
    /**
     * Get API version information
     */
    public function getUser()
    {
        return $this->users->getUser();
    }
}
