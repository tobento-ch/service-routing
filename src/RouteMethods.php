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

/**
 * RouteMethods
 */
trait RouteMethods
{
    /**
     * Create a new Route.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    abstract public function route(string $method, string $uri, mixed $handler): RouteInterface;
    
    /**
     * Create a new Route with GET method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function get(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('GET', $uri, $handler);
    }
    
    /**
     * Create a new Route with POST method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function post(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('POST', $uri, $handler);
    }
    
    /**
     * Create a new Route with PUT method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function put(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('PUT', $uri, $handler);
    }
    
    /**
     * Create a new Route with PATCH method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function patch(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('PATCH', $uri, $handler);
    }
    
    /**
     * Create a new Route with DELETE method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function delete(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('DELETE', $uri, $handler);
    }
    
    /**
     * Create a new Route with HEAD method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function head(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('HEAD', $uri, $handler);
    }
    
    /**
     * Create a new Route with OPTIONS method.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function options(string $uri, mixed $handler): RouteInterface
    {
        return $this->route('OPTIONS', $uri, $handler);
    }    
}