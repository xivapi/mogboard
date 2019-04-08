<?php

namespace App\Service\User;

use App\Entity\User;
use App\Entity\UserAlertQueue;
use App\Repository\UserAlertQueueRepository;
use App\Repository\UserRepository;
use App\Service\ThirdParty\Discord\Discord;
use Delight\Cookie\Cookie;
use Doctrine\ORM\EntityManagerInterface;
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
    /** @var UserAlertQueueRepository */
    private $repositoryAlertQueue;
    /** @var SignInInterface */
    private $sso;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(User::class);
        $this->repositoryAlertQueue = $em->getRepository(UserAlertQueue::class);
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
            $this->checkPatreonTiersForUser($user);
            usleep(200000);
        }
        
        $this->em->clear();
    }
    
    /**
     * Checks the patreon status for an individual user
     */
    public function checkPatreonTiersForUser(User $user)
    {
        $discordId = $user->getSsoDiscordId();

        try {
            $roleTier = Discord::mog()->getUserRole($discordId);
        } catch (\Exception $ex) {
            return false;
        }
    
        // set patreon tier
        $user->setPatron($roleTier ?: 0);
    
        // extra benefits
        if ($roleTier >= 1) {
            //
            // Set Alert Benefits
            //
            $benefits = User::ALERT_LIMITS[$roleTier];

            /** @var UserAlertQueue $queue */
            // grab alert queue
            $alertQueueNumber = null;
            foreach ($this->repositoryAlertQueue->findAll() as $queue) {
                if (empty($queue->getUser())) {
                    $alertQueueNumber = $queue->getNumber();
                    break;
                }
            }

            // update user
            $user
                ->setAlertQueue($alertQueueNumber)
                ->setAlertsMax($benefits['MAX'])
                ->setAlertsMaxNotifications($benefits['MAX_NOTIFICATIONS'])
                ->setAlertNotifyTimeout($benefits['NOTIFY_TIMEOUT'])
                ->setAlertsExpiry($benefits['EXPIRY_TIMEOUT'])
                ->setAlertsUpdateTimeout($benefits['UPDATE_TIMEOUT']);
        }
    
        $this->em->persist($user);
        $this->em->flush();
        
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
    
    public function createPrivateAlertQueues()
    {
        foreach (range(0,100) as $i) {
            $queue = new UserAlertQueue();
            $queue->setNumber($i);
            $this->em->persist($queue);
        }
        
        $this->em->flush();
    }
}
