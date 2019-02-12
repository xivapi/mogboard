<?php

namespace App\Services\User;

use App\Services\User\SSO\SSOAccess;

interface SignInInterface
{
    public function getLoginAuthorizationUrl(): string;
    
    public function setLoginAuthorizationState(): SSOAccess;
    
    public function getAuthorizationToken(): SSOAccess;
}
