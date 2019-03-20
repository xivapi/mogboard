<?php

namespace App\Exceptions;

class GeneralJsonException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct(200,  $message);
    }
}
