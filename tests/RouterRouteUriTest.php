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
 * RouterRouteUriTest tests
 */
class RouterRouteUriTest extends TestCase
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
    
    public function testStaticUri()
    {
        $router = $this->createRouter('GET', 'shop/checkout');
        
        $router->route('GET', 'shop/checkout', function() {
            return 'checkout';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'checkout',
            $routeResponse
        );
    }
    
    public function testStaticUriFirstRouteFoundMatches()
    {
        $router = $this->createRouter('GET', 'shop/checkout');
        
        $router->route('GET', 'shop/checkout', function() {
            return 'checkout first';
        });
        
        $router->route('GET', 'shop/checkout', function() {
            return 'checkout second';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'checkout first',
            $routeResponse
        );
    }
    
    public function testUriWithNamedParameter()
    {
        $router = $this->createRouter('GET', 'shop/product');
        
        $router->route('GET', 'shop/{slug}', function($slug) {
            return $slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'product',
            $routeResponse
        );
    }
    
    public function testUriWithMultipleNamedParameter()
    {
        $router = $this->createRouter('GET', 'shop/product/foo/56');
        
        $router->route('GET', 'shop/{slug}/foo/{id}', function($slug, $id) {
            return $slug.$id;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'product56',
            $routeResponse
        );
    }
    
    public function testUriOptionalParameterExists()
    {
        $router = $this->createRouter('GET', 'shop/product');
        
        $router->route('GET', 'shop/{?slug}', function($slug) {
            return $slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'product',
            $routeResponse
        );
    }
    
    public function testUriOptionalParameterDoesNotExists()
    {
        $router = $this->createRouter('GET', 'shop');
        
        $router->route('GET', 'shop/{?slug}', function($slug) {
            return 'null';
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'null',
            $routeResponse
        );
    }
    
    public function testUriOptionalParameterAtBeginningDoesNotExist()
    {
        $router = $this->createRouter('GET', 'shop');
        
        $router->route('GET', '{?locale}/shop', function($locale) {
            return $locale;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            '',
            $routeResponse
        );
    }

    public function testUriMultipleOptionalParameter()
    {
        $router = $this->createRouter('GET', 'en/shop/slug');
        
        $router->route('GET', '{?locale}/shop/{?slug}', function($locale, $slug) {
            return $locale.$slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'enslug',
            $routeResponse
        );
    }
    
    public function testUriMultipleOptionalParameterLastExist()
    {
        $router = $this->createRouter('GET', 'shop/slug');
        
        $router->route('GET', '{?locale}/shop/{?slug}', function($locale, $slug) {
            return $locale.$slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'slug',
            $routeResponse
        );
    }
    
    public function testUriMultipleOptionalParameterFirstExist()
    {
        $router = $this->createRouter('GET', 'en/shop');
        
        $router->route('GET', '{?locale}/shop/{?slug}', function($locale, $slug) {
            return $locale.$slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en',
            $routeResponse
        );
    }    
    
    public function testUriMultipleOptionalParameterDoesNotExist()
    {
        $router = $this->createRouter('GET', 'shop');
        
        $router->route('GET', '{?locale}/shop/{?slug}', function($locale, $slug) {
            return $locale.$slug;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            '',
            $routeResponse
        );
    }
    
    public function testUriWildcardParameter()
    {
        $router = $this->createRouter('GET', 'shop/foo/bar');
        
        $router->route('GET', 'shop/{path*}', function($path) {
            return $path;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'foo/bar',
            $routeResponse
        );
    }
    
    public function testUriWildcardAndOptionalParameter()
    {
        $router = $this->createRouter('GET', 'shop/foo/bar');
        
        $router->route('GET', '{?locale}/shop/{path*}', function($locale, $path) {
            return $locale.$path;
        });

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'foo/bar',
            $routeResponse
        );
    }     
}