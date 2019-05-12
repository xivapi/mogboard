<?php

namespace App\Exceptions;

use App\Common\Exceptions\ExceptionTrait;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CompanionMarketServerException extends HttpException
{
    use ExceptionTrait;
    
    const CODE    = 400;
    const MESSAGE = 'Invalid server provided for request.';
}
