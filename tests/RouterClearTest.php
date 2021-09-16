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
 * RouterClearTest tests
 */
class RouterClearTest extends TestCase
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
    
    public function testThatRouteUrlAfterClearStillExists()
    {
        $router = $this->createRouter('GET', 'blog');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog');

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        $router->clear();
        
        $this->assertSame(
            'https://example.com/blog',
            (string)$router->url('blog')
        );
    }     
}