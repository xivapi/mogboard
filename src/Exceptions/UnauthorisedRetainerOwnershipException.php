<?php

namespace App\Exceptions;

class UnauthorisedRetainerOwnershipException extends \Exception
{
    const CODE    = 400;
    const MESSAGE = 'User does not own retainer.';

    public function __construct()
    {
        parent::__construct(self::CODE, self::MESSAGE);
    }
}
