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

use Tobento\Service\Collection\Arr;

/**
 * RouteResource
 */
class RouteResource implements RouteResourceInterface
{
    /**
     * @var array<string, mixed> The resource actions.
     */
    protected array $actions = [];

    /**
     * @var null|array The actions to route only.
     */
    protected ?array $only = null;

    /**
     * @var null|array The actions to route except.
     */
    protected ?array $except = null;    
    
    /**
     * @var array The middleware.
     */
    protected array $middleware = [];

    /**
     * @var null|array The middleware mapped to actions.
     */
    protected ?array $middlewareMapped = null;

    /**
     * @var array The parameters.
     */
    protected array $parameters = [];
    
    /**
     * @var array The parameters.
     */
    protected array $sharedParameters = [];    
    
    /**
     * Create a new RouteResource
     *
     * @param RouterInterface $router
     * @param string $name The resource name
     * @param string $controller The controller
     * @param string $placeholder The placeholder name for the uri
     * @param null|string $where The placeholder where constraint
     */        
    public function __construct(
        protected RouterInterface $router,
        protected string $name,
        protected string $controller,
        string $placeholder = 'id',
        ?string $where = '[0-9]+'
    ){        
        // placeholder constraint
        $constraints = ['constraints' => [$placeholder => $where]];
        
        if ($where === null) {
            $constraints = [];
        }
        
        // Default actions
        $this->action('index', 'GET');
        $this->action('create', 'GET', '/create');
        $this->action('store', 'POST');
        $this->action('show', 'GET', '/{'.$placeholder.'}', $constraints);
        $this->action('edit', 'GET', '/{'.$placeholder.'}/edit', $constraints);
        $this->action('update', 'PUT|PATCH', '/{'.$placeholder.'}', $constraints);
        $this->action('delete', 'DELETE', '/{'.$placeholder.'}', $constraints);
    }

    /**
     * Set an action
     *    
     * @param string $action The action name such as 'index'
     * @param string $method The method such as 'GET'
     * @param string $uri The uri
     * @param array<string, mixed> $parameters Any parameters such as ['constraints' => ['id' => '/^[0-9]+$/']]
     * @return static $this
     */
    public function action(string $action, string $method, string $uri = '', array $parameters = []): static
    {        
        $uri = $this->name.$uri;
        
        $name = str_replace(['/', '{', '}', '?', '*'], ['.', '', '', '', ''], $this->name);
        
        $name = strtolower($name).'.'.$action;
        
        $this->actions[$action] = [$method, $uri, $name, $parameters];
        
        return $this;
    }
    
    /**
     * Set the actions to route only
     *    
     * @param array<int, string> $actions The actions ['create']
     * @return static $this
     */
    public function only(array $actions = []): static
    {
        $this->only = $actions;
        return $this;
    }

    /**
     * Set the actions to route except
     *    
     * @param array<int, string> $actions The actions ['create']
     * @return static $this
     */
    public function except(array $actions = []): static
    {
        $this->except = $actions;
        return $this;
    }    
    
    /**
     * Set middleware
     *
     * @param array<int, string> $actions If empty, middleware for all actions
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(
        array $actions,
        mixed ...$middleware,
    ): static {
        $this->middleware[] = [$middleware, $actions];
        return $this;
    }

    /**
     * Set a domain.
     *
     * @param string $domain
     * @return static $this
     */
    public function domain(string ...$domain): static
    {        
        $this->sharedParameters['domain'] = $domain;
        return $this;
    }
    
    /**
     * Add an parameter for an action
     *
     * @param string $action The action name such as 'index'
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $action, string $name, mixed $value): static
    {        
        $this->parameters[$action][$name] = $value;
        return $this;
    }

    /**
     * Get the routes.
     *
     * @return array<int, RouteInterface>
     */
    public function getRoutes(): array
    {
        $actions = $this->getActions();
        
        if ($this->only !== null) {
            $actions = Arr::only($actions, $this->only);
        }
                
        if ($this->except !== null) {
            $actions = Arr::except($actions, $this->except);
        }
        
        $routes = [];
        
        foreach($actions as $action => [$method, $uri, $name, $parameters])
        {
            $route = $this->router->getRouteFactory()->createRoute(
                $this->router,
                $method,
                $uri,
                [$this->controller, $action]
            );
            
            $route->name($name);
            
            // middleware
            if ($middleware = $this->getMiddlewareFor($action)) {
                $route->middleware(...$middleware);
            }
            
            // action parameters
            foreach($parameters as $name => $value) {
                $route->parameter($name, $value);
            }
            
            // global parameters
            if (isset($this->parameters[$action])) {
                foreach($this->parameters[$action] as $name => $value) {
                    $route->parameter($name, $value);
                }         
            }
            
            // shared parameters
            foreach($this->sharedParameters as $name => $value) {
                $route->parameter($name, $value);
            }
            
            $routes[] = $route;
        }

        return $routes;
    }

    /**
     * Get the actions
     *
     * @return array
     */
    protected function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get the middleware for the action
     *
     * @param string $action
     * @return null|array The middleware
     */
    protected function getMiddlewareFor(string $action): ?array
    {
        if ($this->middlewareMapped === null) {
            
            $this->middlewareMapped = [];
            
            foreach($this->middleware as [$middleware, $actions])
            {
                if (empty($actions)) {
                    foreach($this->getActions() as $name => $parameters)
                    {
                        $this->middlewareMapped[$name] = $middleware;    
                    }
                } else {
                    foreach($actions as $name)
                    {
                        $this->middlewareMapped[$name] = $middleware;    
                    }
                }
            }
        }

        return $this->middlewareMapped[$action] ?? null;
    }    
}