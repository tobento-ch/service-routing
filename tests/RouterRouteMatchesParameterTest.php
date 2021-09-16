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
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\RouteNotFoundException;

/**
 * RouterRouteMatchesParameterTest tests
 */
class RouterRouteMatchesParameterTest extends TestCase
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

    public function testMatches()
    {    
        $router = $this->createRouter('GET', 'blog');
        
        $router->get('blog', function() {
            return 'blog';
        })->matches(function(RouteInterface $route): null|RouteInterface {
            return $route;
        });
    
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'blog',
            $routeResponse
        );
    }
    
    public function testMatchesReturnsNull()
    { 
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'blog');
        
        $router->get('blog', function() {
            return 'blog';
        })->matches(function(RouteInterface $route): null|RouteInterface {
            return null;
        });
    
        $matchedRoute = $router->dispatch();
    }
    
    public function testMatchesSkipsIfNotMatches()
    {    
        $router = $this->createRouter('GET', 'blog');
        
        $router->get('blog', function() {
            return 'blog';
        })->matches(function(RouteInterface $route): null|RouteInterface {
            return null;
        });
        
        $router->get('blog', function() {
            return 'blog1';
        })->matches(function(RouteInterface $route): null|RouteInterface {
            return $route;
        });        
    
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'blog1',
            $routeResponse
        );
    }    
}