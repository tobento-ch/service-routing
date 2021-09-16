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

use Tobento\Service\Uri\UriRequest;
use Closure;

/**
 * Router.
 */
class Router implements RouterInterface
{
    use RouteMethods;
    
    /**
     * @var array<int|string, RouteInterface> The routes.
     */    
    protected array $routes = [];
    
    /**
     * @var array<int|string, RouteInterface> The old routes.
     */    
    protected array $oldRoutes = [];    
    
    /**
     * @var mixed The last routable
     */    
    protected mixed $routable = null;
    
    /**
     * @var null|RouteInterface The matched route.
     */    
    protected ?RouteInterface $matchedRoute = null;

    /**
     * @var null|string The base uri.
     */    
    protected ?string $baseUri = null;

    /**
     * @var array The route parameters to get passed to the request attributes.
     */    
    protected array $requestAttributes = ['uri', 'name', 'request_uri'];
    
    /**
     * Create a new Router
     *
     * @param RequestDataInterface $requestData,
     * @param UrlGeneratorInterface $urlGenerator
     * @param RouteDispatcherInterface $routeDispatcher
     * @param RouteHandlerInterface $routeHandler
     * @param MatchedRouteHandlerInterface $matchedRouteHandler
     */    
    public function __construct(
        protected RequestDataInterface $requestData,
        protected UrlGeneratorInterface $urlGenerator,
        protected RouteFactoryInterface $routeFactory,
        protected RouteDispatcherInterface $routeDispatcher,
        protected RouteHandlerInterface $routeHandler,
        protected MatchedRouteHandlerInterface $matchedRouteHandler,
        protected RouteResponseParserInterface $routeResponseParser,
    ) {}

    /**
     * Sets the request data.
     *
     * @param RequestDataInterface $requestData
     * @return void
     */    
    public function setRequestData(RequestDataInterface $requestData): void
    {
        $this->requestData = $requestData;
    }
    
    /**
     * Gets the request data.
     *
     * @return RequestDataInterface
     */    
    public function getRequestData(): RequestDataInterface
    {
        return $this->requestData;
    }
    
    /**
     * Sets the base uri.
     *
     * @param string $baseUri The base uri.
     * @return void
     */    
    public function setBaseUri(string $baseUri): void
    {
        $uriRequest = new UriRequest($this->getRequestData()->uri());
            
        $uriRequest = $uriRequest->withPath(
            $uriRequest->path()->sub($baseUri)
        );
        
        $this->setRequestData(
            $this->getRequestData()->withUri($uriRequest->get())
        );
        
        $this->baseUri = $baseUri;
    }
    
    /**
     * Gets the base uri.
     *
     * @return null|string
     */    
    public function getBaseUri(): ?string
    {
        return $this->baseUri;
    }    

    /**
     * Set the route parameters to get passed to the request attributes.
     *
     * @param array $attributes The route parameters such as ['uri', 'name']
     * @return void
     */    
    public function setRequestAttributes(array $attributes): void
    {
        $this->requestAttributes = $attributes;
    }

    /**
     * Get the route parameters to get passed to the request attributes.
     *
     * @return array The route parameters such as ['uri', 'name']
     */    
    public function getRequestAttributes(): array
    {
        return $this->requestAttributes;
    }
    
    /**
     * Get the url generator.
     *
     * @return UrlGeneratorInterface
     */    
    public function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }
    
    /**
     * Get the route dispatcher.
     *
     * @return RouteDispatcherInterface
     */    
    public function getRouteDispatcher(): RouteDispatcherInterface
    {
        return $this->routeDispatcher;
    }
    
    /**
     * Get the route handler.
     *
     * @return RouteHandlerInterface
     */    
    public function getRouteHandler(): RouteHandlerInterface
    {
        return $this->routeHandler;
    }

    /**
     * Get the matched route handler.
     *
     * @return MatchedRouteHandlerInterface
     */    
    public function getMatchedRouteHandler(): MatchedRouteHandlerInterface
    {
        return $this->matchedRouteHandler;
    }
    
    /**
     * Get the route factory.
     *
     * @return RouteFactoryInterface
     */    
    public function getRouteFactory(): RouteFactoryInterface
    {
        return $this->routeFactory;
    }
    
    /**
     * Get the route response parser.
     *
     * @return RouteResponseParserInterface
     */    
    public function getRouteResponseParser(): RouteResponseParserInterface
    {
        return $this->routeResponseParser;
    }
    
    /**
     * Add a route.
     *
     * @param RouteInterface $route
     * @return static $this
     */
    public function addRoute(RouteInterface $route): static
    {
        if ($route->getName()) {
            $this->routes[$route->getName()] = $route;
        } else {
            $this->routes[] = $route;
        }
        
        return $this;
    }
    
    /**
     * Add routes.
     *
     * @param array<int, RouteInterface> $routes
     * @return static $this
     */
    public function addRoutes(array $routes): static
    {
        foreach($routes as $route)
        {
            $this->addRoute($route);
        }
        
        return $this;
    }
    
    /**
     * Get a route by name or null if not exist.
     *
     * @param string $name
     * @return null|RouteInterface
     */
    public function getRoute(string $name): null|RouteInterface
    {
        $this->addRoutable();
        
        return $this->routes[$name] ?? $this->oldRoutes[$name] ?? null;
    }
     
    /**
     * Get the routes.
     * 
     * @return array<int|string, RouteInterface>
     */
    public function getRoutes(): array
    {
        // add last routable as to keep orders and for names.
        $this->addRoutable();

        return $this->routes;
    }    

    /**
     * Create a new Route.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteInterface
     */
    public function route(string $method, string $uri, mixed $handler): RouteInterface
    {
        $this->addRoutable();
        
        $route = $this->routeFactory->createRoute($this, $method, $uri, $handler);
        
        $this->routable = $route;
        
        return $route;
    }
    
    /**
     * Create a new RouteGroup.
     * 
     * @param string $uri The route uri such as 'foo/{id}'
     * @param Closure $callback
     * @return RouteGroupInterface
     */
    public function group(string $uri, Closure $callback): RouteGroupInterface
    {
        $this->addRoutable();
        
        $group = $this->routeFactory->createRouteGroup($this, $uri, $callback);
        
        $this->routable = $group;
        
        return $group;
    }
    
    /**
     * Create a new RouteResource.
     * 
     * @param string $name The resource name
     * @param string $controller The controller
     * @param string $placeholder The placeholder name for the uri
     * @return RouteResourceInterface
     */
    public function resource(string $name, string $controller, string $placeholder = 'id'): RouteResourceInterface
    {
        $this->addRoutable();
        
        $resource = $this->routeFactory->createRouteResource(
            $this,
            $name,
            $controller,
            $placeholder
        );
        
        $this->routable = $resource;
        
        return $resource;
    }
    
    /**
     * Get the matched route.
     *
     * @return null|RouteInterface The route matched or null.
     */    
    public function getMatchedRoute(): null|RouteInterface
    {
        return $this->matchedRoute;
    }
    
    /**
     * Dispatch the routes.
     *
     * @throws RouteNotFoundException
     * @throws InvalidSignatureException
     * @throws TranslationException
     *
     * @return RouteInterface The route matched.
     */    
    public function dispatch(): RouteInterface
    {
        if ($this->matchedRoute) {
            return $this->matchedRoute;
        }
        
        return $this->matchedRoute = $this->routeDispatcher->dispatch($this);
    }
    
    /**
     * Register a matched event listener.
     *
     * @param string $routeName The route name or null for any route.
     * @param callable $callable
     * @param int $priority The priority. Highest first.
     * @return void
     */    
    public function matched(string $routeName, callable $callable, int $priority = 0): void
    {
        $this->matchedRouteHandler->register($routeName, $callable, $priority);
    }
    
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
    public function url(string $name, array $parameters = []): UrlInterface
    {
        $this->addRoutable();
        
        return $this->routeFactory->createUrl($this, $name, $parameters);
    }
    
    /**
     * Clear
     *
     * @return void
     */
    public function clear(): void
    {
        $this->oldRoutes = array_merge($this->oldRoutes, $this->routes);
        $this->routes = [];
        $this->routable = null;
        $this->matchedRoute = null;
    }

    /**
     * Add routable.
     * 
     * @return void
     */
    protected function addRoutable(): void
    {
        if ($this->routable instanceof RouteInterface) {
            $this->addRoute($this->routable);
        } elseif ($this->routable instanceof RouteGroupInterface) {
            $this->addRoutes($this->routable->getRoutes());            
        } elseif ($this->routable instanceof RouteResourceInterface) {
            $this->addRoutes($this->routable->getRoutes());            
        }
        
        $this->routable = null;
    }   
}