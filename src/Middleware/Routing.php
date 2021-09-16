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
use Tobento\Service\Routing\RouteNotFoundException;

/**
 * Routing middleware.
 */
class Routing implements MiddlewareInterface
{    
    /**
     * Create a new Routing middleware
     *
     * @param RouterInterface $router
     * @param bool $updateMethodFromRequest
     */    
    public function __construct(
        protected RouterInterface $router,
        private bool $updateMethodFromRequest = true,
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
        if ($this->updateMethodFromRequest)
        {
            $requestData = $this->router->getRequestData()->withMethod($request->getMethod());
            $this->router->setRequestData($requestData);
        }
        
        // just dispatch without catching exceptions.
        $matchedRoute = $this->router->dispatch();

        // add route parameters to request.
        foreach($this->router->getRequestAttributes() as $attribute) {
            if ($matchedRoute->hasParameter($attribute)) {
                $request = $request->withAttribute('route.'.$attribute, $matchedRoute->getParameter($attribute));
            }
        }
        
        // call matched route handler for handling registered matched event actions.
        $this->router->getMatchedRouteHandler()->handle($matchedRoute, $request);
        
        // handle the matched route
        $routeResponse = $this->router->getRouteHandler()
                                      ->handle($matchedRoute, $request);
        
        $response = $handler->handle($request);
        
        // parse the route response.
        return $this->router->getRouteResponseParser()->parse($response, $routeResponse);
    }
}