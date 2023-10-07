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
 * RouterTransRouteTest tests
 */
class RouterTransRouteTest extends TestCase
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
    
    public function testWithoutLocaleUri()
    {
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('de')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'ueber-uns',
            $routeResponse
        );
    }
    
    public function testWithoutLocaleUriMultipleTrans()
    {
        $router = $this->createRouter('GET', 'ueber-uns/mehr');
        
        $router->get('{about}/{more}', function($about, $more) {
            return $about.'/'.$more;
        })->name('about')
          ->locale('de')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about'])
          ->trans('more', ['de' => 'mehr', 'en' => 'more']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'ueber-uns/mehr',
            $routeResponse
        );
    }
    
    public function testWithoutLocaleUriMultipleTransThrowsRouteNotFoundExceptionIfUriDoesNotMatch()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'ueber-uns/foo');
        
        $router->get('{about}/{more}', function($about, $more) {
            return $about.'/'.$more;
        })->name('about')
          ->locale('de')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about'])
          ->trans('more', ['de' => 'mehr', 'en' => 'more']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithoutLocaleUriThrowsRouteNotFoundExceptionIfLocaleDoesNotMatchRequestUri()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('en')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithoutLocaleUriThrowsTranslationExceptionIfNoLocaleIsSet()
    {
        $this->expectException(TranslationException::class);
        
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithoutLocaleUriAndLocaleFallbacks()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('fr')
          ->localeFallbacks(['fr' => 'en'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'about',
            $routeResponse
        );
    }
    
    public function testWithoutLocaleUriThrowsTranslationExceptionIfTransDoesNotExist()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('fr')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithoutLocaleUriUrl()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('de')
          ->localeFallbacks(['fr' => 'en'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $this->assertSame(
            'https://example.com/ueber-uns',
            (string)$router->url('about')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string)$router->url('about')->locale('en')
        );
        
        $this->assertSame(
            'https://example.com/ueber-uns',
            (string)$router->url('about')->locale('de')
        );
        
        $this->assertSame(
            'https://example.com/about',
            (string)$router->url('about')->locale('fr')
        );
    }
    
    public function testWithoutLocaleUriUrlThrowsTranslationExceptionIfTransDoesNotExistForLocale()
    {
        $this->expectException(TranslationException::class);
        
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('de')
          ->localeFallbacks(['fr' => 'en'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        (string)$router->url('about')->locale('us');
    }
    
    public function testWithoutLocaleUriUrlTranslatedMethodWithoutLocalesSet()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locale('de')
          ->localeFallbacks(['fr' => 'en'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/ueber-uns',
                'en' => 'https://example.com/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testWithoutLocaleUriUrlTranslatedMethodWithLocalesSet()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locales(['de', 'en', 'fr'])
          ->locale('de')
          ->localeFallbacks(['fr' => 'en'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/ueber-uns',
                'en' => 'https://example.com/about',
                'fr' => 'https://example.com/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testWithoutLocaleUriUrlTranslatedMethodWithLocalesSetButWithoutFallbackSkipsUrlIfNoTrans()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{about}', function($about) {
            return $about;
        })->name('about')
          ->locales(['de', 'en', 'fr'])
          ->locale('de')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/ueber-uns',
                'en' => 'https://example.com/about',
            ],
            $router->url('about')->translated()
        );
    }
    
    public function testWithLocaleUri()
    {
        $router = $this->createRouter('GET', 'de/ueber-uns');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return $locale.'/'.$about;
        })->name('about')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'de/ueber-uns',
            $routeResponse
        );
    }
    
    public function testWithLocaleUriThrowsTranslationExceptionIfLocaleCouldNotBeDetected()
    {
        $this->expectException(TranslationException::class);
        
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return $locale.'/'.$about;
        })->name('about')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithLocaleUriMultipleTransThrowsRouteNotFoundExceptionIfUriDoesNotMatch()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'de/ueber-uns/more');
        
        $router->get('{?locale}/{about}/{more}', function($about, $more) {
            return $about.'/'.$more;
        })->name('about')
          ->locales(['de', 'en'])
          ->trans('more', ['de' => 'mehr', 'en' => 'more'])
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testWithLocaleUriAndLocaleOmitShouldBeLocaleIfNotSet()
    {
        $router = $this->createRouter('GET', 'ueber-uns');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return $locale.'/'.$about;
        })->name('about')
          ->localeOmit('de')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'de/ueber-uns',
            $routeResponse
        );
    }
    
    public function testThrowsTranslationExceptionIfLocaleUrlDoesNotExists()
    {
        $this->expectException(TranslationException::class);
        
        $router = $this->createRouter('GET', 'en/about');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return $locale.'/'.$about;
        })->name('about')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $router->url('about')->locale('fr');
    }
    
    public function testUrlFallsbackToFirstLocaleIfLocaleSetDoesNotExist()
    {
        $router = $this->createRouter('GET', 'about');
        
        $router->get('{?locale}/{about}', function($locale, $about) {
            return $locale.'/'.$about;
        })->name('about')
          ->locales(['de', 'en'])
          ->locale('fr')
          ->trans('about', ['de' => 'ueber-uns', 'en' => 'about']);
        
        $this->assertSame('https://example.com/de/ueber-uns', (string)$router->url('about'));
    }    
}