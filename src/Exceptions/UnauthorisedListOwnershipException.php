<?php

namespace App\Exceptions;

class UnauthorisedListOwnershipException extends \Exception
{
    const CODE    = 400;
    const MESSAGE = 'User does not own list.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
