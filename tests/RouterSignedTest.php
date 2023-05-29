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
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;

/**
 * RouterSignedTest tests
 */
class RouterSignedTest extends TestCase
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
    
    public function testRouteSigned()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe');
        
        $url = (string) $router->url('unsubscribe', ['user' => 5])->sign();
        
        $uri = str_replace('https://example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'unsubscribe/5',
            $routeResponse
        );
    }
    
    public function testRouteSignedThrowsInvalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);
        
        $router = $this->createRouter('GET', 'unsubscribe/5/ef05716c4350c12bfe0770a90d922163733c9afc3a8cbff17b47d6b2914bc0ag');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testRouteSignedThrowsInvalidSignatureExceptionWithMissingExpiring()
    {
        $this->expectException(InvalidSignatureException::class);
        
        $router = $this->createRouter('GET', 'unsubscribe/5');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testRouteSignedWithCustomResponse()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {

            $matchedRoute = $router->getMatchedRoute();
            $requestUri = $router->getRequestData()->uri();

            if (! $router->getUrlGenerator()->hasValidSignature($matchedRoute->getUri(), $requestUri)) {
                // handle invalid signature.
                return 'invalid';
            }
            
            return 'unsubscribe/'.$user;
            
        })->signed('unsubscribe', validate: false);
        
        $url = (string) $router->url('unsubscribe', ['user' => 5])->sign();
        
        $uri = str_replace('https://example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'unsubscribe/5',
            $routeResponse
        );
    }
    
    public function testUrlSignWithNoExpiring()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {            
            return 'unsubscribe/'.$user;   
        })->signed('unsubscribe');
        
        $this->assertSame(
            'https://example.com/unsubscribe/5/c0b119f15637c3da263d5956278d7e9f8567321d377e8f36e0b884daefbfb06a',
            (string) $router->url('unsubscribe', ['user' => 5])->sign()
        );
    }
    
    public function testUrlSignWithExpiring()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {            
            return 'unsubscribe/'.$user;   
        })->signed('unsubscribe');
        
        $this->assertSame(
            'https://example.com/unsubscribe/5/6077d42cd0ced1625fa880d8535ef972622cac7bc667c38e86eb5a0be70f71d0/1634767200',
            (string) $router->url('unsubscribe', ['user' => 5])->sign('2021-10-21')
        );
    }
    
    public function testUrlSignWithNoExpiringWithQuery()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {            
            return 'unsubscribe/'.$user;   
        })->signed('unsubscribe');
        
        $this->assertSame(
            'https://example.com/unsubscribe/5?signature=c04619ec56180716b35f840c108d0b188ebc57557ebe5c9a2d115986d42e3ffd',
            (string) $router->url('unsubscribe', ['user' => 5])->sign(withQuery: true)
        );
    }
    
    public function testUrlSignWithExpiringWithQuery()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function(RouterInterface $router, $user) {            
            return 'unsubscribe/'.$user;   
        })->signed('unsubscribe');
        
        $this->assertSame(
            'https://example.com/unsubscribe/5?expires=1634767200&signature=ec3e188cd35b7ea6ea69cd8939868fbd4c89d78d510b35c7abe4f93b5764a138',
            (string) $router->url('unsubscribe', ['user' => 5])->sign('2021-10-21', true)
        );
    }
    
    public function testRouteSignedWithDomain()
    {
        $router = $this->createRouter('GET', 'unsubscribe');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe')->domain('example.com');
        
        $url = (string) $router->url('unsubscribe', ['user' => 5])->sign();
        
        $this->assertTrue(str_starts_with($url, 'https://example.com'));
        
        $uri = str_replace('https://example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'unsubscribe/5',
            $routeResponse
        );
    }
    
    public function testRouteSignedWithMultipleDomain()
    {
        $router = $this->createRouter('GET', 'unsubscribe', 'sub.example.com');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe')->domain('example.com')->domain('sub.example.com');
        
        $url = (string) $router->url('unsubscribe', ['user' => 5])->sign();

        $this->assertTrue(str_starts_with($url, 'https://sub.example.com'));
        
        $uri = str_replace('https://sub.example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'unsubscribe/5',
            $routeResponse
        );
    }
    
    public function testRouteSignedWithMultipleDomainSpecificDomain()
    {
        $router = $this->createRouter('GET', 'unsubscribe', 'example.com');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe')->domain('example.com')->domain('example.ch')->domain('example.de');
        
        $url = (string) $router->url('unsubscribe', ['user' => 5])->domain('example.ch')->sign();

        $this->assertTrue(str_starts_with($url, 'https://example.ch'));
        
        $uri = str_replace('https://example.ch/', '', $url);
        
        $router = $this->createRouter('GET', 'unsubscribe', 'example.ch');
        
        $router->get('unsubscribe/{user}', function($user) {
            return 'unsubscribe/'.$user;
        })->signed('unsubscribe')->domain('example.com')->domain('example.ch')->domain('example.de');
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'unsubscribe/5',
            $routeResponse
        );
    }    
}