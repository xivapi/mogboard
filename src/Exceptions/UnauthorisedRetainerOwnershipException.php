<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;

class UnauthorisedRetainerOwnershipException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'User does not own retainer.';
}
