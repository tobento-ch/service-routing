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
 * RouterRouteMethodTest tests
 */
class RouterRouteMethodTest extends TestCase
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
    
    public function testRouteMethod()
    {
        $router = $this->createRouter('GET', '');
        
        $router->route('GET', '', function() {
            return 'home';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'home',
            $routeResponse
        );
    }
    
    public function testRouteMethodWithMultipleHttpMethods()
    {
        $router = $this->createRouter('POST', '');
        
        $router->route('GET|POST', '', function() {
            return 'home';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'home',
            $routeResponse
        );
        
        $router = $this->createRouter('GET', '');
        
        $router->route('GET|POST', '', function() {
            return 'home';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'home',
            $routeResponse
        );
    }
    
    public function testRouteMethodWithWildcardMethods()
    {
        $router = $this->createRouter('PUT', '');
        
        $router->route('*', '', function() {
            return 'home';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'home',
            $routeResponse
        );
    }    
    
    public function testRouteMethodThrowsRouteNotFoundException()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('POST', '');
        
        $router->route('GET', '', function() {
            return 'home';
        });

        $router->dispatch();
    }
    
    public function testRouteMethodVerbs()
    {
        $router = $this->createRouter('GET', '');
        
        $router->get('', function() {
            return 'get';
        });
        
        $router->head('', function() {
            return 'head';
        });
        
        $router->post('', function() {
            return 'post';
        });
        
        $router->put('', function() {
            return 'put';
        });
        
        $router->patch('', function() {
            return 'patch';
        });
        
        $router->delete('', function() {
            return 'delete';
        });
        
        $router->options('', function() {
            return 'options';
        });
        
        $routes = $router->getRoutes();
        
        $methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        
        foreach($methods as $method)
        {
            $router->clear();
            $router->addRoutes($routes);
            $router->setRequestData($router->getRequestData()->withMethod($method));
            
            $matchedRoute = $router->dispatch();
            $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
            
            $this->assertSame(
                strtolower($method),
                $routeResponse
            );            
        }
    }    
}