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
 * RouteFactory.
 */
class RouteFactory implements RouteFactoryInterface
{
    /**
     * Create a new Route
     *
     * @param RouterInterface $router
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */    
    public function createRoute(
        RouterInterface $router,
        string $method,
        string $uri,
        mixed $handler
    ): RouteInterface {
        return new Route(
            $router->getUrlGenerator(),
            $method,
            $uri,
            $handler
        );
    }
    
    /**
     * Create a new RouteGroup
     *
     * @param RouterInterface $router
     * @param string $uri The route uri such as 'foo/{id}'
     * @param Closure $callback
     * @return RouteGroupInterface
     */    
    public function createRouteGroup(
        RouterInterface $router,
        string $uri,
        Closure $callback
    ): RouteGroupInterface {
        return new RouteGroup(
            $router,
            $uri,
            $callback
        );
    }
    
    /**
     * Create a new RouteResource.
     *
     * @param RouterInterface $router
     * @param string $name The resource name
     * @param string $controller The controller
     * @param string $placeholder The placeholder name for the uri
     * @return RouteResourceInterface
     */
    public function createRouteResource(
        RouterInterface $router,
        string $name,
        string $controller,
        string $placeholder = 'id'
    ): RouteResourceInterface {
        return new RouteResource(
            $router,
            $name,
            $controller,
            $placeholder
        );
    }
    
    /**
     * Create a new Url.
     *
     * @param RouterInterface $router
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     * @return UrlInterface
     */    
    public function createUrl(RouterInterface $router, string $name, array $parameters): UrlInterface
    {
        return new Url($router, $name, $parameters);
    }    
}