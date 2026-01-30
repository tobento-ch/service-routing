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
    
    public function testRouteSignedWithCustomValidationPasses()
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
    
    public function testRouteSignedWithCustomValidationFails()
    {
        $router = $this->createRouter('GET', 'unsubscribe');

        $router->get('unsubscribe/{user}', function (RouterInterface $router, $user) {

            $matchedRoute = $router->getMatchedRoute();
            $requestUri   = $router->getRequestData()->uri();

            // Custom validation logic
            if (! $router->getUrlGenerator()->hasValidSignature($matchedRoute->getUri(), $requestUri)) {
                return 'invalid';
            }

            return 'unsubscribe/'.$user;

        })->signed('unsubscribe', validate: false);

        // Create a valid signed URL
        $url = (string) $router->url('unsubscribe', ['user' => 5])->sign(withQuery: true);

        // Break the signature so it becomes invalid
        $uri = str_replace('signature=', 'signature=broken', $url);
        $uri = str_replace('https://example.com/', '', $uri);

        $router->setRequestData($router->getRequestData()->withUri($uri));

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);

        $this->assertSame('invalid', $routeResponse);
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
            'https://example.com/unsubscribe/5/0630ffde0d7ed4f5449e976eeb02ce7275404d7997e05219c6c0fe636c76e66a/1634774400',
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
            'https://example.com/unsubscribe/5?expires=1634774400&signature=fc4b1ce3138e2c1a6215c5108bb1551fa9ac1f702a305609eaae5d6888604d38',
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
    
    public function testRouteSignedWithWildcardUriSignatureIsCleaned()
    {
        $router = $this->createRouter('GET', 'file');
        
        $router->get('file/{id}/{path*}', function(RouterInterface $router, $id, $path) {
            return $id.'='.$path;
        })->signed(name: 'file', validate: true);
        
        $url = (string) $router->url('file', ['id' => 5, 'path' => 'foo/bar/baz'])->sign();
        
        $uri = str_replace('https://example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('5=foo/bar/baz', $routeResponse);
    }
    
    public function testRouteSignedWithWildcardUriSignatureAndExpirationIsCleaned()
    {
        $router = $this->createRouter('GET', 'file');
        
        $router->get('file/{id}/{path*}', function(RouterInterface $router, $id, $path) {
            return $id.'='.$path;
        })->signed(name: 'file', validate: true);
        
        $url = (string) $router->url('file', ['id' => 5, 'path' => 'foo/bar/baz'])->sign(expiration: time() + 3600);
        
        $uri = str_replace('https://example.com/', '', $url);
        
        $router->setRequestData($router->getRequestData()->withUri($uri));
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('5=foo/bar/baz', $routeResponse);
    }
    
    public function testRouteSignedWithWildcardUriInvalidSignatureThrows()
    {
        $this->expectException(InvalidSignatureException::class);

        $router = $this->createRouter('GET', 'file');

        $router->get('file/{id}/{path*}', function () {
            return 'should-not-run';
        })->signed(name: 'file', validate: true);

        // Invalid signature + expiration in path
        $uri = 'file/5/foo/bar/baz/invalid/invalid';

        $router->setRequestData($router->getRequestData()->withUri($uri));

        $router->dispatch();
    }
    
    public function testRouteSignedWithWildcardUriMissingExpirationThrows()
    {
        $this->expectException(InvalidSignatureException::class);

        $router = $this->createRouter('GET', 'file');

        $router->get('file/{id}/{path*}', function () {
            return 'should-not-run';
        })->signed(name: 'file', validate: true);

        // Missing expiration segment
        $uri = 'file/5/foo/bar/baz/abc123';

        $router->setRequestData($router->getRequestData()->withUri($uri));

        $router->dispatch();
    }
    
    public function testRouteSignedWithWildcardUriCustomValidationReceivesCleanedPath()
    {
        $router = $this->createRouter('GET', 'file');

        $router->get('file/{id}/{path*}', function (RouterInterface $router, $id, $path) {

            // Custom validation
            $matched = $router->getMatchedRoute();
            $uri = $router->getRequestData()->uri();

            if (! $router->getUrlGenerator()->hasValidSignature($matched->getUri(), $uri)) {
                return 'invalid';
            }

            return $id.'='.$path;

        })->signed(name: 'file', validate: false);

        $url = (string) $router->url('file', [
            'id' => 5,
            'path' => 'foo/bar/baz'
        ])->sign();

        $uri = str_replace('https://example.com/', '', $url);

        $router->setRequestData($router->getRequestData()->withUri($uri));

        $matched = $router->dispatch();
        $response = $router->getRouteHandler()->handle($matched);

        $this->assertSame('5=foo/bar/baz', $response);
    }
}