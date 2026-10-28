<?php

namespace App\Libs\Security;

use App\Attributes\RequireSecurityPassword;
use Illuminate\Routing\Route;
use ReflectionMethod;

class SecurityPasswordRequirement
{
    public function forRoute(?Route $route): ?RequireSecurityPassword
    {
        $controller = $route?->getControllerClass();
        $method = $route?->getActionMethod();
        if (! $controller || ! $method || ! method_exists($controller, $method)) {
            return null;
        }
        $attributes = (new ReflectionMethod($controller, $method))->getAttributes(RequireSecurityPassword::class);

        return $attributes === [] ? null : $attributes[0]->newInstance();
    }
}
