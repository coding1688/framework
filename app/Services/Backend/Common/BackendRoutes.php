<?php

namespace App\Services\Backend\Common;

use App\Libs\Security\SecurityPasswordRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use ReflectionNamedType;

class BackendRoutes
{
    public function __construct(private readonly SecurityPasswordRequirement $securityPasswords) {}

    public function isJson(?RoutingRoute $route): bool
    {
        if (! $route || $route->getDomain() !== config('platform.backend_domain')) {
            return false;
        }
        $controller = $route->getControllerClass();
        $method = $route->getActionMethod();
        if (! $controller || ! method_exists($controller, $method)) {
            return false;
        }

        $type = (new ReflectionMethod($controller, $method))->getReturnType();

        return $type instanceof ReflectionNamedType && is_a($type->getName(), JsonResponse::class, true);
    }

    /** @return array<string, array{url: string, method: string, security_password?: array{required: true, mode: string}}> */
    public function all(): array
    {
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->getDomain() !== config('platform.backend_domain') || ! $this->isJson($route) || ! $route->getName()) {
                continue;
            }
            $parameters = array_fill_keys($route->parameterNames(), '__id__');
            $routes[$route->getName()] = [
                'url' => route($route->getName(), $parameters, false),
                'method' => $route->methods()[0],
            ];
            if ($requirement = $this->securityPasswords->forRoute($route)) {
                $routes[$route->getName()]['security_password'] = $requirement->metadata();
            }
        }

        return $routes;
    }
}
