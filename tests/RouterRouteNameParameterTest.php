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

/**
 * RouterRouteNameParameterTest tests
 */
class RouterRouteNameParameterTest extends TestCase
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
        
        $router->addRoute($route);
        
        $this->assertSame(
            $route,
            $router->getRoute('home')
        );
    }
    
    public function testRouteWithSameNameParameterOverwritesPrevious()
    {
        $router = $this->createRouter('GET', '');
        
        $route = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        $route->name('home');
        
        $routeHome = $router->getRouteFactory()->createRoute($router, 'GET', '', function() {});
        $routeHome->name('home');

        $router->addRoutes([$route, $routeHome]);
        
        $this->assertSame(
            $routeHome,
            $router->getRoute('home')
        );
                
        $this->assertSame(
            1,
            count($router->getRoutes())
        );
    }    
}