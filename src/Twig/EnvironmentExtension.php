<?php

namespace App\Twig;

use App\Services\Common\Environment;
use Twig\Extension\AbstractExtension;

class EnvironmentExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('env', [$this, 'getEnvVar']),
            new \Twig_SimpleFunction('environment', [$this, 'getEnvironment']),
        ];
    }
    /**
     * Get an environment variable
     */
    public function getEnvVar($var)
    {
        return getenv($var);
    }
    
    /**
     * Get the current site environment
     */
    public function getEnvironment()
    {
        return constant(Environment::CONSTANT);
    }
}
