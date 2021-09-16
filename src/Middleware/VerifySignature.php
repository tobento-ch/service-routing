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
use Tobento\Service\Routing\InvalidSignatureException;

/**
 * VerifySignature middleware.
 */
class VerifySignature implements MiddlewareInterface
{
    /**
     * Create a new VerifySignature
     *
     * @param RouterInterface $router
     */
    public function __construct(
        protected RouterInterface $router,
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
        $matchedRoute = $this->router->getMatchedRoute();
        
        if (is_null($matchedRoute)) {
            throw new InvalidSignatureException(null, 'Invalid signature as no matched route');
        }
            
        if ($this->router->getUrlGenerator()->hasValidSignature(
            $matchedRoute->getUri(),
            $this->router->getRequestData()->uri()
        )) {
            return $handler->handle($request);
        }
        
        throw new InvalidSignatureException($matchedRoute, 'Invalid signature');
    }    
}