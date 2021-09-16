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

namespace Tobento\Service\Routing\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteInterface;

/**
 * RouteHandler middleware.
 */
class RouteHandler implements MiddlewareInterface
{    
    /**
     * Create a new RouteHandler middleware
     *
     * @param RouteInterface $route The route to handle
     * @param RouterInterface $router
     */    
    public function __construct(
        private RouteInterface $route,
        private RouterInterface $router,
    ) {}
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->route->parameter('middleware', null);
        
        $routeResponse = $this->router->getRouteHandler()->handle($this->route, $request);
        
        $response = $handler->handle($request);
        
        return $this->router->getRouteResponseParser()->parse($response, $routeResponse);
    }    
}