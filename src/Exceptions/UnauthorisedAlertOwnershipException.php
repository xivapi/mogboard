<?php

namespace App\Exceptions;

class UnauthorisedAlertOwnershipException extends \Exception
{
    const CODE    = 400;
    const MESSAGE = 'User does not own alert.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
