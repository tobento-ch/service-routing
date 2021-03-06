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
 * RouteGroup
 */
class RouteGroup implements RouteGroupInterface
{
    use RouteMethods;
    
    /**
     * @var array<int, RouteInterface> The routes.
     */    
    protected array $routes = [];
    
    /**
     * @var array<mixed>
     */    
    protected mixed $routables = []; 
    
    /**
     * @var array The route parameters.
     */
    protected array $parameters = [];
    
    /**
     * @var array<int, mixed> The route methods to call.
     */
    protected array $methods = [];    
    
    /**
     * Create a new RouteGroup
     *
     * @param RouterInterface $router
     * @param string $uri The route uri such as 'foo/{id}'
     * @param Closure $callback
     */        
    public function __construct(
        protected RouterInterface $router,
        protected string $uri,
        protected Closure $callback
    ) {
        call_user_func_array($this->callback, [$this]);
    }

    /**
     * Set a route middleware
     *    
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(mixed ...$middleware): static
    {
        $this->parameters['middleware'] = $middleware;
        return $this;
    }
    
    /**
     * Add a route uri constraint
     *
     * @param string $key The constraint key such as 'id' 
     * @param mixed $constraint
     * @return static $this
     */
    public function where(string $key, mixed $constraint): static
    {        
        $this->parameters['constraints'][$key] = $constraint;
        return $this;
    }    
    
    /**
     * Set a route domain.
     *
     * @param string $domain
     * @return static $this
     */
    public function domain(string ...$domain): static
    {
        $this->parameters['domain'] = $domain;
        return $this;
    }    

    /**
     * Set a base url for the given route
     *    
     * @param string $baseUrl
     * @return static $this
     */
    public function baseUrl(string $baseUrl): static
    {        
        $this->methods[] = ['baseUrl', 'base_url', [$baseUrl]];
        return $this;
    }
 
    /**
     * Set the locale.
     *    
     * @param string $locale The default or current locale.
     * @return static $this
     */
    public function locale(string $locale): static
    {
        $this->methods[] = ['locale', 'locale', [$locale]];
        
        return $this;
    }
    
    /**
     * Set the locales.
     *    
     * @param array<int, string> $locales The supported locales
     * @return static $this
     */
    public function locales(array $locales): static
    {
        $this->methods[] = ['locales', 'locales', [$locales]];
        return $this;
    }
    
    /**
     * The locale to omit on uri.
     *
     * @param string $localeOmit
     * @return static $this
     */
    public function localeOmit(string $localeOmit): static
    {
        $this->parameters['locale_omit'] = $localeOmit;
        
        return $this;
    }    
    
    /**
     * Set the locale name.
     *    
     * @param string $localeName The locale name in uri.
     * @return static $this
     */
    public function localeName(string $localeName): static
    {
        $this->parameters['locale_name'] = $localeName;
        
        return $this;
    }    
    
    /**
     * Set the locale fallbacks. ['de' => 'en']
     *    
     * @param array<string, string> $localeFallbacks
     * @return static $this
     */
    public function localeFallbacks(array $localeFallbacks): static
    {
        $this->parameters['locale_fallbacks'] = $localeFallbacks;
        
        return $this;
    }

    /**
     * Set locale base urls for the given route.
     *    
     * @param array<string, string> $baseUrls ['en' => 'en.example.com']
     * @return static $this
     */
    public function localeBaseUrls(array $baseUrls): static
    {        
        $this->parameters['locale_base_urls'] = $baseUrls;
        
        return $this;
    }    
    
    /**
     * Add a parameter
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $name, mixed $value): static
    {        
        $this->parameters[$name] = $value;
        return $this;
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
        $route = $this->router->getRouteFactory()->createRoute(
            $this->router,
            $method,
            $this->uri.'/'.$uri,
            $handler
        );
        
        $this->routables[] = $route;
        
        return $route;
    }
    
    /**
     * Create a new RouteGroup.
     * 
     * @param string $method The method such as 'GET'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     * @return RouteGroupInterface
     */
    public function group(string $uri, Closure $callback): RouteGroupInterface
    {
        $group = $this->router->getRouteFactory()->createRouteGroup(
            $this->router,
            $this->uri.'/'.$uri,
            $callback
        );
        
        $this->routables[] = $group;
        
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
        $resource = $this->router->getRouteFactory()->createRouteResource(
            $this->router,
            $this->uri.'/'.$name,
            $controller,
            $placeholder
        );
        
        $this->routables[] = $resource;
        
        return $resource;
    }    
    
    /**
     * Get the routes.
     *
     * @return array<int, RouteInterface>
     */
    public function getRoutes(): array
    {                
        $this->addRoutables();
        
        return $this->routes;
    }
    
    /**
     * Add routables.
     * 
     * @return void
     */
    protected function addRoutables(): void
    {
        foreach($this->routables as $routable)
        {
            if ($routable instanceof RouteInterface) {
                $this->routes[] = $routable;
            } elseif ($routable instanceof RouteGroupInterface) {
                $this->routes = array_merge($this->routes, $routable->getRoutes());
            } elseif ($routable instanceof RouteResourceInterface) {
                $this->routes = array_merge($this->routes, $routable->getRoutes());
            }
        }
        
        foreach($this->routes as $route)
        {            
            foreach($this->parameters as $name => $value)
            {
                if (! $route->hasParameter($name)) {
                    $route->parameter($name, $value);
                }
            }
            
            foreach($this->methods as [$method, $name, $params])
            {
                if (! $route->hasParameter($name)) {
                    $route->$method(...$params);
                }
            }
        }
        
        $this->routables = [];
    }    
}