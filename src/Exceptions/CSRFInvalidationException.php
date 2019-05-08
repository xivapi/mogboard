<?php

namespace App\Exceptions;

class CSRFInvalidationException extends \Exception
{
    const CODE    = 400;
    const MESSAGE = 'Could not confirm the CSRF token from SSO Provider. Please try again.';
    
    public function __construct()
    {
        parent::__construct(self::MESSAGE, self::CODE);
    }
}
