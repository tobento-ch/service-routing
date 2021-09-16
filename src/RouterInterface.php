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
 * RouterInterface.
 */
interface RouterInterface extends RouteMethodsInterface
{
    /**
     * Sets the request data.
     *
     * @param RequestDataInterface $requestData
     * @return void
     */    
    public function setRequestData(RequestDataInterface $requestData): void;
    
    /**
     * Gets the request data.
     *
     * @return RequestDataInterface
     */    
    public function getRequestData(): RequestDataInterface;
    
    /**
     * Sets the base uri.
     *
     * @param string $baseUri The base uri.
     * @return void
     */    
    public function setBaseUri(string $baseUri): void;
    
    /**
     * Gets the base uri.
     *
     * @return null|string
     */    
    public function getBaseUri(): ?string;    

    /**
     * Set the route parameters to get passed to the request attributes.
     *
     * @param array $attributes The route parameters such as ['uri', 'name']
     * @return void
     */    
    public function setRequestAttributes(array $attributes): void;

    /**
     * Get the route parameters to get passed to the request attributes.
     *
     * @return array The route parameters such as ['uri', 'name']
     */    
    public function getRequestAttributes(): array;
    
    /**
     * Get the url generator.
     *
     * @return UrlGeneratorInterface
     */    
    public function getUrlGenerator(): UrlGeneratorInterface;
    
    /**
     * Get the route dispatcher.
     *
     * @return RouteDispatcherInterface
     */    
    public function getRouteDispatcher(): RouteDispatcherInterface;
    
    /**
     * Get the route handler.
     *
     * @return RouteHandlerInterface
     */    
    public function getRouteHandler(): RouteHandlerInterface;

    /**
     * Get the matched route handler.
     *
     * @return MatchedRouteHandlerInterface
     */    
    public function getMatchedRouteHandler(): MatchedRouteHandlerInterface;
    
    /**
     * Get the route factory.
     *
     * @return RouteFactoryInterface
     */    
    public function getRouteFactory(): RouteFactoryInterface;
    
    /**
     * Get the route response parser.
     *
     * @return RouteResponseParserInterface
     */    
    public function getRouteResponseParser(): RouteResponseParserInterface;   

    /**
     * Add a route.
     *
     * @param RouteInterface $route
     * @return static $this
     */
    public function addRoute(RouteInterface $route): static;
    
    /**
     * Add routes.
     *
     * @param array<int, RouteInterface> $routes
     * @return static $this
     */
    public function addRoutes(array $routes): static;
    
    /**
     * Get a route by name or null if not exist.
     *
     * @param string $name
     * @return null|RouteInterface
     */
    public function getRoute(string $name): null|RouteInterface;
    
    /**
     * Get the routes.
     * 
     * @return array<int|string, RouteInterface>
     */
    public function getRoutes(): array;
    
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
     * @param string $uri The route uri such as 'foo/{id}'
     * @param Closure $callback
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
     * Get the matched route.
     *
     * @return null|RouteInterface The route matched or null.
     */    
    public function getMatchedRoute(): null|RouteInterface;
    
    /**
     * Dispatch the routes.
     *
     * @throws RouteNotFoundException
     * @throws InvalidSignatureException
     * @throws TranslationException
     *
     * @return RouteInterface The route matched.
     */    
    public function dispatch(): RouteInterface;
    
    /**
     * Register a matched event listener.
     *
     * @param string $routeName The route name or null for any route.
     * @param callable $callable
     * @param int $priority The priority. Highest first.
     * @return void
     */    
    public function matched(string $routeName, callable $callable, int $priority = 0): void;
    
    /**
     * Create a new Url.
     *
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     *
     * @throws UrlException
     *
     * @return UrlInterface
     */    
    public function url(string $name, array $parameters = []): UrlInterface;
    
    /**
     * Clear
     *
     * @return void
     */
    public function clear(): void;
}