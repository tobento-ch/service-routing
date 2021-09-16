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

namespace Tobento\Service\Routing\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\RequestDataInterface;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\UrlGeneratorInterface;
use Tobento\Service\Routing\UrlInterface;
use Tobento\Service\Routing\UrlException;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteFactoryInterface;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\RouteDispatcherInterface;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\RouteHandlerInterface;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\MatchedRouteHandlerInterface;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\RouteResponseParserInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareDispatcher;
use Tobento\Service\Middleware\AutowiringMiddlewareFactory;
use Tobento\Service\Middleware\FallbackHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\Routing\Test\Mock\Middleware;
use Tobento\Service\Routing\Test\Mock\RequestAttributesMiddleware;

/**
 * RouterMiddlewareTest tests
 */
class RouterMiddlewareTest extends TestCase
{
    /**
     * @var null|ContainerInterface
     */    
    protected null|ContainerInterface $container = null;
    
    protected function container(): ContainerInterface
    {
        if (is_null($this->container)) {
            $this->container = new \Tobento\Service\Container\Container();
        }
        
        return $this->container;
    }
    
    protected function createRouter(ServerRequestInterface $request): RouterInterface {
        
        $container = $this->container();

        $router = new Router(
            new RequestData(
                $request->getMethod(),
                (string)$request->getUri()->withScheme('')->withUserInfo('')->withHost(''),
                $request->getUri()->getHost()
            ),
            new UrlGenerator(
                $request->getUri()->getHost(),
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        
        $router->setBaseUri('/');
        
        $container->set(RouterInterface::class, $router);
        
        return $router;
    }
    
    protected function createMiddlewareDispatcher(): MiddlewareDispatcherInterface
    {
        $container = $this->container();
            
        $dispatcher = new MiddlewareDispatcher(
            new FallbackHandler((new Psr17Factory())->createResponse(200)),
            new AutowiringMiddlewareFactory($container)
        );
        
        $container->set(MiddlewareDispatcherInterface::class, $dispatcher);
        
        return $dispatcher;
    }
    
    protected function createServerRequest(
        string $method = 'GET',
        string $uri = '' 
    ): ServerRequestInterface {
        
        $request = (new Psr17Factory())->createServerRequest($method, $uri);
        
        $container = $this->container();
        
        $container->set(ServerRequestInterface::class, $request);
        
        return $request;
    }    

    public function testRoutingMiddleware()
    {
        $request = $this->createServerRequest('GET', 'https://example.com/blog');
        $router = $this->createRouter($request);
        
        $router->get('blog', function() {
            return 'blog';
        });
            
        $middlewareDispatcher = $this->createMiddlewareDispatcher();
        
        $middlewareDispatcher->add(
            \Tobento\Service\Routing\Middleware\Routing::class,
        );

        $response = $middlewareDispatcher->handle($request);
        
        $this->assertSame(
            'blog',
            (string)$response->getBody()
        );
    }
    
    public function testMethodOverrideMiddleware()
    {
        $request = $this->createServerRequest('POST', 'https://example.com/blog')
                        ->withParsedBody(['_method' => 'PUT']);
        
        $router = $this->createRouter($request);
        
        $router->put('blog', function() {
            return 'blog';
        });
            
        $middlewareDispatcher = $this->createMiddlewareDispatcher();
        
        $middlewareDispatcher->add(
            \Tobento\Service\Routing\Middleware\MethodOverride::class,
            \Tobento\Service\Routing\Middleware\Routing::class,
        );

        $response = $middlewareDispatcher->handle($request);
        
        $this->assertSame(
            'blog',
            (string)$response->getBody()
        );
    }
    
    public function testRouteMiddleware()
    {
        $request = $this->createServerRequest('GET', 'https://example.com/blog');
        $router = $this->createRouter($request);
        
        $router->get('blog', function() {
            return 'blog';
        })->middleware(Middleware::class);
            
        $middlewareDispatcher = $this->createMiddlewareDispatcher();
        
        $middlewareDispatcher->add(
            \Tobento\Service\Routing\Middleware\Routing::class,
        );

        $response = $middlewareDispatcher->handle($request);
        
        $this->assertSame(
            'blogMiddlewareAfter',
            (string)$response->getBody()
        );
    }
    
    public function testRquestAttributesWithPreRoutingMiddleware()
    {
        $request = $this->createServerRequest('GET', 'https://example.com/blog');
        $router = $this->createRouter($request);
        
        $router->setRequestAttributes(['name']);
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog');
            
        $middlewareDispatcher = $this->createMiddlewareDispatcher();
        
        $middlewareDispatcher->add(
            \Tobento\Service\Routing\Middleware\PreRouting::class,
            RequestAttributesMiddleware::class,
            \Tobento\Service\Routing\Middleware\Routing::class,
        );

        $response = $middlewareDispatcher->handle($request);
        
        $this->assertSame(
            'blogblog',
            (string)$response->getBody()
        );
    }     
}