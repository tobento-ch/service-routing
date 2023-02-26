<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Routing;

use Closure;

/**
 * RouteGroupInterface
 */
interface RouteGroupInterface extends RouteMethodsInterface, RouteI18MethodsInterface
{
    /**
     * Set a route middleware
     *    
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(mixed ...$middleware): static;
    
    /**
     * Add a route uri constraint
     *
     * @param string $key The constraint key such as 'id' 
     * @param mixed $constraint
     * @return static $this
     */
    public function where(string $key, mixed $constraint): static;

    /**
     * Set a base url for the given route
     *    
     * @param string $baseUrl
     * @return static $this
     */
    public function baseUrl(string $baseUrl): static;
    
    /**
     * Add a parameter
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $name, mixed $value): static;
    
    /**
     * Create a new Route.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function route(string $method, string $uri, mixed $handler): RouteInterface;
    
    /**
     * Create a new RouteGroup.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteGroupInterface
     */
    public function group(string $uri, Closure $callback): RouteGroupInterface;
    
    /**
     * Create a new RouteResource.
     * 
     * @param string $name The resource name
     * @param string $controller The controller
     * @param string $placeholder The placeholder name for the uri
     * @return RouteResourceInterface
     */
    public function resource(string $name, string $controller, string $placeholder = 'id'): RouteResourceInterface;
    
    /**
     * Get the routes.
     *
     * @return array<int, RouteInterface>
     */
    public function getRoutes(): array;
}