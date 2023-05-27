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
use Tobento\Service\Routing\RouteInterface;
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
 * RouterRouteDomainTest
 */
class RouterRouteDomainTest extends TestCase
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
    
    public function testDomain()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.com');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('blog', $routeResponse);
        $this->assertSame('https://example.com/blog', (string)$router->url('blog'));
    }
    
    public function testDomainFails()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->domain('sub.example.com');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testMultipleDomains()
    {
        $router = $this->createRouter('GET', 'blog', 'sub.example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.com')->domain('sub.example.com');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('blog', $routeResponse);
        $this->assertSame('https://sub.example.com/blog', (string)$router->url('blog'));
        $this->assertSame('https://example.com/blog', (string)$router->url('blog')->domain('example.com'));
        $this->assertSame('https://sub.example.com/blog', (string)$router->url('blog'));
    }
    
    public function testUrl()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->domain('example.com')->name('blog');

        $this->assertSame('https://example.com/blog', (string)$router->url('blog'));
    }
    
    public function testUrlWithOtherDomainAsRouter()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.de');

        $this->assertSame('https://example.de/blog', (string)$router->url('blog'));
    }
    
    public function testUrlWithInvalidDomainSpecifiedFallsbackToSpecified()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.de');

        $this->assertSame('https://example.de/blog', (string)$router->url('blog')->domain('example.ch'));
    }
    
    public function testUrlDomainedMethod()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.ch')->domain('example.de');

        $this->assertSame(
            [
                'example.ch' => 'https://example.ch/blog',
                'example.de' => 'https://example.de/blog',
            ],
            $router->url('blog')->domained()
        );
    }
    
    public function testUrlDomainedMethodWithoutSpecifiedDomainsReturnsEmptyArray()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog');

        $this->assertSame(
            [],
            $router->url('blog')->domained()
        );
    }
    
    public function testDomainSpecificRouteParameters()
    {
        $router = $this->createRouter('GET', 'fr/se-presente', 'example.ch');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return [$locale, $about];
        })
        ->name('about')
        ->locales(['de'])
        ->trans('about', ['de' => 'default-ueber-uns', 'en' => 'default-about', 'fr' => 'default-se-presente'])
        ->domain('example.ch', function(RouteInterface $route): void {
            $route->locales(['de', 'fr'])
                ->locale('fr')
                ->localeOmit('de')
                ->localeFallbacks(['fr' => 'de'])
                ->trans('about', ['de' => 'ueber-uns', 'fr' => 'se-presente']);
        })
        ->domain('example.de', function(RouteInterface $route): void {
            $route->locales(['de', 'en'])
                ->locale('en')
                ->localeOmit('en')
                ->localeFallbacks([]);
        });
        
        $this->assertSame('https://example.ch/fr/se-presente', (string)$router->url('about'));
        
        $this->assertSame([
            'de' => 'https://example.ch/ueber-uns',
            'fr' => 'https://example.ch/fr/se-presente',
        ], $router->url('about')->translated());
        
        $this->assertSame([
            'example.ch' => 'https://example.ch/fr/se-presente',
            'example.de' => 'https://example.de/default-about',
        ], $router->url('about')->domained());        
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(['fr', 'se-presente'], $routeResponse);
        
        $this->assertSame('https://example.de/default-about', (string)$router->url('about')->domain('example.de'));
        
        $this->assertSame([
            'de' => 'https://example.de/de/default-ueber-uns',
            'en' => 'https://example.de/default-about',
        ], $router->url('about')->domain('example.de')->translated());
    }
}