<?php

namespace App\Services\User\Discord;

class CsrfInvalidException extends \Exception
{
    const ERROR = 'Could not confirm the CSRF token from SSO Provider. Please try again.';
}
