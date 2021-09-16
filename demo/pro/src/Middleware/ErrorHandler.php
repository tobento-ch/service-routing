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

namespace Tobento\Demo\Routing\Pro\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\TranslationException;
use Tobento\Service\View\ViewInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * ErrorHandler
 */
class ErrorHandler implements MiddlewareInterface
{
    /**
     * Create a new ErrorHandler middleware
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param ContainerInterface $container
     */    
    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected ContainerInterface $container,
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
        try {
            return $handler->handle($request);
        } catch (RouteNotFoundException $e) {
            
            $view = $this->container->get(ViewInterface::class);
            
            $html = $view->render('article', ['title' => '404', 'description' => 'Page not found!']);
            
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write($html);
            
            return $response;
            
        } catch (InvalidSignatureException $e) {
            
            $view = $this->container->get(ViewInterface::class);
            
            $html = $view->render('article', ['title' => '404', 'description' => 'Page has expired or is invalid!']);
            
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write($html);
            
            return $response;
            
        } catch (TranslationException $e) {
            
            $view = $this->container->get(ViewInterface::class);
            
            $html = $view->render('article', ['title' => '404', 'description' => 'Page not found!']);
            
            $response = $this->responseFactory->createResponse(404);
            $response->getBody()->write($html);
            
            return $response;
        } /*catch (Throwable $e) {
            
            $view = $this->container->get(ViewInterface::class);
            
            $html = $view->render('article', ['title' => '500', 'description' => 'Something went wrong!']);
            
            $response = $this->responseFactory->createResponse(500);
            $response->getBody()->write($html);
            
            return $response;
        }*/      
    }    
}