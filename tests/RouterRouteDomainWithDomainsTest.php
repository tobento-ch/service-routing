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
use Tobento\Service\Routing\Domains;
use Tobento\Service\Routing\Domain;

/**
 * RouterRouteDomainWithDomainsTest
 */
class RouterRouteDomainWithDomainsTest extends TestCase
{   
    protected function createRouter(
        string $method = 'GET',
        string $uri = '',
        string $domain = 'example.com'
    ): RouterInterface {

        $domains = new Domains(
            new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
            new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
        );
        
        $container = new \Tobento\Service\Container\Container();

        $router = new Router(
            new RequestData($method, $uri, $domain),
            new UrlGenerator(
                'https://example.com',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory($domains),
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
        $router = $this->createRouter('GET', 'blog', 'ch.localhost');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.ch');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('blog', $routeResponse);
        $this->assertSame('http://ch.localhost/blog', (string)$router->url('blog'));
    }
    
    public function testDomainFails()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'blog', 'example.ch');
        
        $router->get('blog', function() {
            return 'blog';
        })->domain('example.ch');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testMultipleDomains()
    {
        $router = $this->createRouter('GET', 'blog', 'ch.localhost');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.de')->domain('example.ch');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('blog', $routeResponse);
        $this->assertSame('http://ch.localhost/blog', (string)$router->url('blog'));
        $this->assertSame('http://de.localhost/blog', (string)$router->url('blog')->domain('example.de'));
        $this->assertSame('http://ch.localhost/blog', (string)$router->url('blog'));
    }
    
    public function testUrl()
    {
        $router = $this->createRouter('GET', 'blog', 'de.localhost');
        
        $router->get('blog', function() {
            return 'blog';
        })->domain('de.localhost')->name('blog');

        $this->assertSame('http://de.localhost/blog', (string)$router->url('blog'));
    }
    
    public function testUrlWithOtherDomainAsRouter()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.de');

        $this->assertSame('http://de.localhost/blog', (string)$router->url('blog'));
    }
    
    public function testUrlWithInvalidDomainSpecifiedFallsbackToSpecified()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.de');

        $this->assertSame('http://de.localhost/blog', (string)$router->url('blog')->domain('example.fr'));
    }
    
    public function testUrlDomainedMethod()
    {
        $router = $this->createRouter('GET', 'blog', 'example.com');
        
        $router->get('blog', function() {
            return 'blog';
        })->name('blog')->domain('example.ch')->domain('example.de');

        $this->assertSame(
            [
                'ch.localhost' => 'http://ch.localhost/blog',
                'de.localhost' => 'http://de.localhost/blog',
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
        $router = $this->createRouter('GET', 'fr/se-presente', 'ch.localhost');
        
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
        
        $this->assertSame('http://ch.localhost/fr/se-presente', (string)$router->url('about'));
        
        $this->assertSame([
            'de' => 'http://ch.localhost/ueber-uns',
            'fr' => 'http://ch.localhost/fr/se-presente',
        ], $router->url('about')->translated());
        
        $this->assertSame([
            'ch.localhost' => 'http://ch.localhost/fr/se-presente',
            'de.localhost' => 'http://de.localhost/default-about',
        ], $router->url('about')->domained());        
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(['fr', 'se-presente'], $routeResponse);
        
        $this->assertSame('http://de.localhost/default-about', (string)$router->url('about')->domain('example.de'));
        
        $this->assertSame([
            'de' => 'http://de.localhost/de/default-ueber-uns',
            'en' => 'http://de.localhost/default-about',
        ], $router->url('about')->domain('example.de')->translated());
    }
}