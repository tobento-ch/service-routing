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
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\Test\Mock\Middleware;
use Tobento\Service\Routing\Test\Mock\AnotherMiddleware;

/**
 * RouterRouteParametersTest tests
 */
class RouterRouteParametersTest extends TestCase
{   
    protected function createRouter(
        string $method = 'GET',
        string $uri = '',
        string $domain = 'example.com'
    ): RouterInterface {
        
        $container = new \Tobento\Service\Container\Container();

        $router = new Router(
            new RequestData($method, $uri, $domain),
            new UrlGenerator(
                'https://example.com',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        
        $container->set(RouterInterface::class, $router);
        
        return $router;
    }
    
    public function testNameParameter()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        $route->name('home');
        
        $this->assertSame(
            'home',
            $route->getParameter('name')
        );
        
        $this->assertSame(
            'home',
            $route->getName()
        );
    }
    
    public function testMiddlewareParameter()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        $route->middleware(Middleware::class, AnotherMiddleware::class);
        
        $this->assertSame(
            [Middleware::class, AnotherMiddleware::class],
            $route->getParameter('middleware')
        );
    }
    
    public function testParameter()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        $route->parameter('key', 'value');
        
        $this->assertSame(
            'value',
            $route->getParameter('key')
        );
    }
    
    public function testGetMethodMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        
        $this->assertSame(
            'GET',
            $route->getMethod()
        );
    }
    
    public function testGetUriMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', function() {});
        
        $this->assertSame(
            'uri',
            $route->getUri()
        );
    }
    
    public function testGetHandlerMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', 'handler');
        
        $this->assertSame(
            'handler',
            $route->getHandler()
        );
    }
    
    public function testGetParametersMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', 'handler');
        $route->parameter('key', 'value');
        
        $this->assertSame(
            ['key' => 'value'],
            $route->getParameters()
        );
    }
    
    public function testHasParameterMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', 'handler');
        $route->parameter('key', 'value');
        
        $this->assertTrue(
            $route->hasParameter('key')
        );
        
        $this->assertFalse(
            $route->hasParameter('foo')
        );
    }
    
    public function testGetParameterMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', 'handler');
        $route->parameter('key', 'value');
        
        $this->assertSame(
            'value',
            $route->getParameter('key')
        );
        
        $this->assertSame(
            null,
            $route->getParameter('foo')
        );
    }     
}