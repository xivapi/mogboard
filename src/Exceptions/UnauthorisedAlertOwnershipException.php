<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;

class UnauthorisedAlertOwnershipException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'User does not own alert.';
}
