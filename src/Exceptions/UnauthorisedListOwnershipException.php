<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;

class UnauthorisedListOwnershipException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'User does not own list.';
}
