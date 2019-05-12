<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;

class InvalidAlertCreationException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid alert creation data.';
}
