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

/**
 * MethodOverride
 */
class MethodOverride implements MiddlewareInterface
{
    /**
     * Create a new MethodOverride middleware
     *
     * @param string $methodName
     * @param string $headerMethodName
     */    
    public function __construct(
        private string $methodName = '_method',
        private string $headerMethodName = 'X-Http-Method-Override',
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
        $headerMethod = $request->getHeaderLine($this->headerMethodName);

        if ($headerMethod)
        {
            $request = $request->withMethod($headerMethod);
        }
        elseif (strtoupper($request->getMethod()) === 'POST')
        {
            $body = $request->getParsedBody();

            if (is_array($body) && !empty($body[$this->methodName]))
            {
                $request = $request->withMethod($body[$this->methodName]);
            }

            if ($request->getBody()->eof())
            {
                $request->getBody()->rewind();
            }
        }

        return $handler->handle($request);
    }    
}