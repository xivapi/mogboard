<?php

namespace App\Exceptions;

class InvalidAlertCreationException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Invalid alert creation data.', 400, null);
    }
}
