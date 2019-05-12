<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;

class GeneralJsonException extends \Exception
{
    use ExceptionTrait;
    
    const CODE    = 200;
    const MESSAGE = 'General Json Exception';
}
