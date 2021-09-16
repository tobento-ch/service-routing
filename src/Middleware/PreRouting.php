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
use Tobento\Service\Routing\InvalidSignatureException;

/**
 * PreRouting middleware used if you want matched route info
 * in the request attributes for next middlewares.
 */
class PreRouting implements MiddlewareInterface
{            
    /**
     * Create a new PreRouting middleware
     *
     * @param RouterInterface $router
     * @param bool $updateMethodFromRequest
     */    
    public function __construct(
        private RouterInterface $router,
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
        
        return $handler->handle($request);
    }    
}