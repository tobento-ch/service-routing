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
use Tobento\Service\Routing\TranslationException;

/**
 * RouterLocalizedRouteTest tests
 */
class RouterLocalizedRouteTest extends TestCase
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
    
    public function testWithLocale()
    {
        $router = $this->createRouter('GET', 'de/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'de/about',
            $routeResponse
        );
    }
    
    public function testWithLocaleUsesRequestUriLocale()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en/about',
            $routeResponse
        );
    }
    
    public function testWithLocaleWithoutRequestUriLocaleUsesLocaleSet()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'de/about',
            $routeResponse
        );
    }

    public function testWithLocales()
    {        
        $router = $this->createRouter('GET', 'de/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'de/about',
            $routeResponse
        );
    }
    
    public function testWithLocalesThrowsRouteNotFoundExceptionIfInvalidLocale()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'fr/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
    }

    public function testWithLocaleOmit()
    {        
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en/about',
            $routeResponse
        );
    }
    
    public function testWithLocalesWithoutLocaleThrowsTranslationExceptionIfNoLocaleInSlug()
    {
        $this->expectException(TranslationException::class);
        
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en']);
        
        $matchedRoute = $router->dispatch();
    }    
    
    public function testWithLocaleOmitThrowsRouteNotFoundExceptionIfLocaleIsInSlug()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithLocaleName()
    {        
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?loc}/about', function($loc) {
            return $loc.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeName('loc')
          ->locale('de');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en/about',
            $routeResponse
        );
    }

    public function testLocaleUriAtAnotherPosition()
    {        
        $router = $this->createRouter('GET', 'foo/en/about');
        
        $router->get('foo/{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('en');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en/about',
            $routeResponse
        );
    }
    
    public function testLocaleUriAtAnotherPositionWithoutLocaleInSlug()
    {        
        $router = $this->createRouter('GET', 'foo/about');
        
        $router->get('foo/{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'en/about',
            $routeResponse
        );
    }
    
    public function testUrl()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('de');
                
        $this->assertSame(
            'https://example.com/de/about',
            (string) $router->url('about')
        );
        
        $this->assertSame(
            'https://example.com/en/about',
            (string) $router->url('about')->locale('en')
        );
    }
    
    public function testUrlWithLocaleOmit()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
                
        $this->assertSame(
            'https://example.com/about',
            (string) $router->url('about')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string) $router->url('about')->locale('en')
        );
    }
    
    public function testCreatesUrlEvenIfNotInLocales()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('en');
        
        $this->assertSame(
            'https://example.com/fr/about',
            (string) $router->url('about')->locale('fr')
        );
    }
    
    public function testUrlThatParameterHasPriorityOverLocale()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
        
        $this->assertSame(
            'https://example.com/us/about',
            (string) $router->url('about', ['locale' => 'us'])->locale('fr')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string) $router->url('about', ['locale' => 'en'])->locale('fr')
        );
    }

    public function testUrlTranslatedMethodWithoutLocalesAndLocaleReturnsEmptyArray()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about');
        
        $this->assertSame(
            [],
            $router->url('about')->translated()
        );
    }
    
    public function testUrlTranslatedMethodWithoutLocalesUsesLocale()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locale('en');
        
        $this->assertSame(
            [
                'en' => 'https://example.com/en/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testUrlTranslatedMethodWithoutLocaleOmit()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('en');
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/about',
                'en' => 'https://example.com/en/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testUrlTranslatedMethodWithLocaleOmitShouldOmitLocale()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/about',
                'en' => 'https://example.com/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testUrlTranslatedMethodWithSpecificLocalesAreGeneratedEvenIfNotDefinedInLocales()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('en');
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/about',
                'fr' => 'https://example.com/fr/about',
            ],
            $router->url('about')->translated(['de', 'fr'])
        );
    }    
    
    public function testUrlHasTranslationMethod()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->localeOmit('en')
          ->locale('en');
        
        $this->assertTrue($router->url('about')->hasTranslation('de'));
        $this->assertTrue($router->url('about')->hasTranslation('en'));
        $this->assertFalse($router->url('about')->hasTranslation('fr'));
    }
    
    public function testWithBaseUrl()
    {
        $router = $this->createRouter('GET', 'de/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('de')
          ->localeOmit('en')
          ->localeBaseUrls(['en' => 'https://en.example.com']);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/about',
                'en' => 'https://en.example.com/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testWithoutLocale()
    {
        $router = $this->createRouter('GET', 'de/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('de/about', $routeResponse);
    }
    
    public function testWithoutLocaleAndWithLocales()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('en/about', $routeResponse);
    }
    
    public function testWithoutLocaleThrowsRouteNotFoundExceptionIfRequestLocaleIsNotInLocales()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'fr/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en']);
        
        $matchedRoute = $router->dispatch();
    }

    public function testUrlWithoutLocaleAndLocales()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about');
        
        $this->assertSame(
            'https://example.com/de/about',
            (string) $router->url('about')->locale('de')
        );
        
        $this->assertSame(
            'https://example.com/en/about',
            (string) $router->url('about')->locale('en')
        );
        
        $this->assertSame(
            'https://example.com/fr/about',
            (string) $router->url('about')->locale('fr')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string) $router->url('about')
        );
    }
    
    public function testUrlWithoutLocaleAndLocalesTranslatedMethod()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about');

        $this->assertSame(
            [],
            $router->url('about')->translated()
        );
    }
    
    public function testUrlWithoutLocaleAndLocalesTranslatedMethodSpecificLocales()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about');

        $this->assertSame(
            [
                'de' => 'https://example.com/de/about',
                'en' => 'https://example.com/en/about',
            ],
            $router->url('about')->translated(['de', 'en'])
        );
    }    
    
    public function testUrlWithoutLocale()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')
          ->locales(['de', 'en']);
        
        $this->assertSame(
            'https://example.com/de/about',
            (string) $router->url('about')->locale('de')
        );
        
        $this->assertSame(
            'https://example.com/en/about',
            (string) $router->url('about')->locale('en')
        );
        
        $this->assertSame(
            'https://example.com/fr/about',
            (string) $router->url('about')->locale('fr')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string) $router->url('about')
        );
    } 
    
    public function testUrlTranslatedMethodIfOnlyLocaleIsset()
    {
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{locale}/about', function($locale) {
            return $locale.'/about';
        })->name('about')->locale('en');
        
        $this->assertSame(
            'https://example.com/en/about',
            (string) $router->url('about')
        );
        
        $this->assertSame(
            'https://example.com/de/about',
            (string) $router->url('about')->locale('de')
        );
        
        $this->assertSame(
            [
                'en' => 'https://example.com/en/about',
            ],
            $router->url('about')->translated()
        );
    }      
}