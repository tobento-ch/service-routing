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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Routing\Middleware\RouteHandler as MiddlewareRouteHandler;
use Tobento\Service\Routing\RouteResponseParserInterface;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Autowire\Autowire;
use RuntimeException;
use Closure;

/**
 * Default route handler.
 */
class RouteHandler implements RouteHandlerInterface
{
    /**
     * @var Autowire
     */    
    protected Autowire $autowire;
    
    /**
     * Create a new RouteHandler
     *
     * @param ContainerInterface $container
     * @param null|Closure $containerSync
     */    
    public function __construct(
        protected ContainerInterface $container,
        protected null|Closure $containerSync = null,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Handles the route.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The return value of the handler called.
     */    
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): mixed
    {        
        // Handle middleware if any.
        if (is_array($route->getParameter('middleware')))
        {            
            if (
                ! $this->container->has(MiddlewareDispatcherInterface::class)
                || is_null($request)
            ) {
                return $this->callRouteHandler($route, $request);
            }
            
            $middlewareDispatcher = $this->container->get(MiddlewareDispatcherInterface::class);
            
            $middlewareDispatcher->add(...$route->getParameter('middleware'));
                
            $middlewareDispatcher->add([MiddlewareRouteHandler::class, 'route' => $route]);

            $response = $middlewareDispatcher->handle($request);
                        
            // sync container for autowiring.            
            if (!is_null($this->containerSync)) {
                call_user_func_array($this->containerSync, [$this->container, $request, $response]);    
            }
            
            return $response;
        }
                
        return $this->callRouteHandler($route, $request);
    }
    
    /**
     * Call the route handler.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The called function result.
     */
    protected function callRouteHandler(RouteInterface $route, null|ServerRequestInterface $request): mixed
    {
        $handler = $route->getHandler();
        $requestParams = $route->getParameter('request_parameters', []);
        
        if (is_array($handler) && isset($handler[2]) && is_array($handler[2]))
        {
            $requestParams = array_merge($requestParams, $handler[2]);
        }
        
        if (!is_null($request)) {
            $requestParams['request'] = $request;
        }

        return $this->autowire->call($handler, $requestParams);
    }
}