<?php

namespace App\Service\User;

use App\Entity\User;
use App\Entity\UserAlert;
use App\Repository\UserRepository;
use App\Service\ThirdParty\Discord\Discord;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Users
{
    const COOKIE_SESSION_NAME = 'session';
    const COOKIE_SESSION_DURATION = (60 * 60 * 24 * 30);

    /** @var EntityManagerInterface */
    private $em;
    /** @var UserRepository */
    private $repository;
    /** @var SignInInterface */
    private $sso;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(User::class);
    }

    /**
     * Set the single sign in provider
     */
    public function setSsoProvider(SignInInterface $sso)
    {
        $this->sso = $sso;
        return $this;
    }

    /**
     * Get user repository
     */
    public function getRepository(): UserRepository
    {
        return $this->repository;
    }

    /**
     * Get the current logged in user
     */
    public function getUser($mustBeOnline = true): ?User
    {
        $session = Cookie::get(self::COOKIE_SESSION_NAME);
        if (!$session || $session === 'x') {
            if ($mustBeOnline) {
                throw new NotFoundHttpException();
            }

            return null;
        }

        /** @var User $user */
        $user = $this->repository->findOneBy([
            'session' => $session,
        ]);

        if ($mustBeOnline && !$user) {
            throw new NotFoundHttpException();
        }

        return $user;
    }

    /**
     * Is the current user online?
     */
    public function isOnline()
    {
        return !empty($this->getUser(false));
    }

    /**
     * Sign in
     */
    public function login(): string
    {
        return $this->sso->getLoginAuthorizationUrl();
    }

    /**
     * Logout a user
     */
    public function logout(): void
    {
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue('x')->setMaxAge(-1)->setPath('/')->save();
        $cookie->delete();
    }

    /**
     * Authenticate
     */
    public function authenticate(): User
    {
        // look for their user if they already have an account
        $sso  = $this->sso->setLoginAuthorizationState();
        $user = $this->repository->findOneBy([
            'email' => $sso->email,
        ]);

        // handle user info during login process
        $user = $this->handleUser($sso, $user);

        // set cookie
        $cookie = new Cookie(self::COOKIE_SESSION_NAME);
        $cookie->setValue($user->getSession())->setMaxAge(self::COOKIE_SESSION_DURATION)->setPath('/')->save();

        return $user;
    }

    /**
     * Set user information
     */
    public function handleUser(\stdClass $sso, User $user = null): User
    {
        $user = $user ?: new User();
        $user
            ->setSso($sso->name)
            ->setUsername($sso->username)
            ->setEmail($sso->email)
            ->generateSession();

        // set discord info
        if ($sso->name === SignInDiscord::NAME) {
            $user
                ->setSsoDiscordId($sso->id)
                ->setSsoDiscordAvatar($sso->avatar)
                ->setSsoDiscordTokenAccess($sso->tokenAccess)
                ->setSsoDiscordTokenExpires($sso->tokenExpires)
                ->setSsoDiscordTokenRefresh($sso->tokenRefresh);
        }

        $this->save($user);
        return $user;
    }

    /**
     * Update a user
     */
    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Set the last url the user was on
     */
    public function setLastUrl(Request $request)
    {
        $request->getSession()->set('last_url', $request->getUri());
    }
    
    /**
     * Get the last url
     */
    public function getLastUrl(Request $request)
    {
        return $request->getSession()->get('last_url');
    }
    
    /**
     * Checks the patreon status of all users against the discord channel.
     */
    public function checkPatreonTiersForAllUsers()
    {
        /** @var User $user */
        foreach ($this->repository->findAll() as $user) {
            $this->checkPatreonTierForUser($user);
        }
        
        $this->em->clear();
        return true;
    }
    
    public function checkPatreonTierForUser(User $user)
    {
        $console = new ConsoleOutput();
        $console->writeln("User: {$user->getUsername()} {$user->getSsoDiscordId()}");
        
        try {
            $tier = Discord::mog()->getUserRole($user->getSsoDiscordId());
        } catch (\Exception $ex) {
            $console->writeln($ex->getMessage());
            return false;
        }
    
        // check or set default
        $tier = $tier ?: 0;
    
        // set patreon tier
        $user->setPatron($tier);
    
        /**
         * Alerts!
         */
    
        // Get Alert Limits
        $benefits = User::ALERT_LIMITS[$tier];
    
        // update user
        $user
            ->setAlertsMax($benefits['MAX'])
            ->setAlertsMaxNotifications($benefits['MAX_NOTIFICATIONS'])
            ->setAlertsNotifyTimeout($benefits['NOTIFY_TIMEOUT'])
            ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT']);
    
        $this->em->persist($user);
        $this->em->flush();
        usleep(100000);
        return true;
    }

    /**
     * Clear alert tracking values
     */
    public function clearAlertTracking()
    {
        /** @var User $user */
        foreach ($this->repository->findAll() as $user) {
            $user->setAlertsNotificationCount(0);

            $this->em->persist($user);
            $this->em->flush();
        }
    }

    /**
     * This extends the expiry time of the users alerts.
     */
    public function refreshUsersAlerts()
    {
        $user = $this->getUser(false);

        if ($user === null) {
            return;
        }

        /** @var UserAlert $alert */
        foreach ($user->getAlerts() as $alert) {
            $alert->setExpiry(
                time() + $user->getAlertsExpiry()
            );

            $this->em->persist($alert);
        }

        $this->em->flush();
    }
    
    public function getPatrons()
    {
        return [
            4 => $this->repository->findBy([ 'patron' => 4 ]),
            3 => $this->repository->findBy([ 'patron' => 3 ]),
            2 => $this->repository->findBy([ 'patron' => 2 ]),
            1 => $this->repository->findBy([ 'patron' => 1 ]),

            9 => $this->repository->findBy([ 'patron' => 9 ]),
        ];
    }
}
