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

/**
 * RouterTest tests
 */
class RouterTest extends TestCase
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

    public function testGetRequestDataMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceOf(
            RequestDataInterface::class,
            $router->getRequestData()
        );
    }
    
    public function testSetAndGetRequestReturnsSameInstance()
    {
        $router = $this->createRouter();
        
        $requestData = new RequestData('GET', '', 'example.com');
        
        $router->setRequestData($requestData);
            
        $this->assertSame(
            $requestData,
            $router->getRequestData()
        );
    }    
    
    public function testSetAndGetBaseUriMethod()
    {
        $router = $this->createRouter();
        
        $this->assertSame(
            null,
            $router->getBaseUri()
        );
        
        $router->setBaseUri('foo/bar/');
        
        $this->assertSame(
            'foo/bar/',
            $router->getBaseUri()
        );
    }
    
    public function testBaseUriGetsSubstractedFromRequestUri()
    {
        $router = $this->createRouter('GET', '/path/app/');
        
        $router->setBaseUri('/path/app/');
        
        $router->get('', function() {
            return 'home';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'home',
            $routeResponse
        );
    }    
    
    public function testSetAndGetRequestAttributesMethod()
    {
        $router = $this->createRouter();
        
        $attributes = ['uri', 'name'];
        
        $router->setRequestAttributes($attributes);
        
        $this->assertSame(
            $attributes,
            $router->getRequestAttributes()
        );
    }
    
    public function testGetUrlGeneratorMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            UrlGeneratorInterface::class,
            $router->getUrlGenerator()
        );
    }
    
    public function testGetRouteDispatcherMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            RouteDispatcherInterface::class,
            $router->getRouteDispatcher()
        );
    }
    
    public function testGetRouteHandlerMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            RouteHandlerInterface::class,
            $router->getRouteHandler()
        );
    }
    
    public function testGetMatchedRouteHandlerMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            MatchedRouteHandlerInterface::class,
            $router->getMatchedRouteHandler()
        );
    } 
    
    public function testGetRouteFactoryMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            RouteFactoryInterface::class,
            $router->getRouteFactory()
        );
    } 
    
    public function testGetRouteResponseParserMethod()
    {
        $router = $this->createRouter();
        
        $this->assertInstanceof(
            RouteResponseParserInterface::class,
            $router->getRouteResponseParser()
        );
    }
    
    public function testAddAndGetRouteMethod()
    {
        $router = $this->createRouter();
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', function() {});
        $route->name('foo');
        
        $router->addRoute($route);
        
        $this->assertSame(
            $route,
            $router->getRoute('foo')
        );
    }
    
    public function testGetRouteMethodReturnsNullIfNotExist()
    {
        $router = $this->createRouter();
        
        $this->assertSame(
            null,
            $router->getRoute('foo')
        );
    }
    
    public function testAddRoutesMethod()
    {
        $router = $this->createRouter();
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', 'uri', function() {});
        $route->name('foo');
        
        $router->addRoutes([$route]);
        
        $this->assertSame(
            $route,
            $router->getRoute('foo')
        );
    }

    public function testUrlMethod()
    {
        $router = $this->createRouter();
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog');
        
        $this->assertInstanceof(
            UrlInterface::class,
            $router->url('blog')
        );
    }
    
    public function testUrlMethodThrowsUrlException()
    {
        $this->expectException(UrlException::class);
        
        $router = $this->createRouter();
        
        $router->get('blog', function() {
            return 'blog';
        });
        
        $router->url('blog');
    }     
}