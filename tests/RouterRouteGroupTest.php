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
use Tobento\Service\Routing\RouteGroupInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\Test\Mock\Middleware;
use Tobento\Service\Routing\Test\Mock\AnotherMiddleware;

/**
 * RouterRouteGroupTest tests
 */
class RouterRouteGroupTest extends TestCase
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
    
    public function testGroupWithStaticUri()
    {
        $router = $this->createRouter('GET', 'admin/products');
        
        $router->group('admin', function(RouteGroupInterface $group) {
            
            $group->get('products', function() {
                return 'admin.products';
            });
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'admin.products',
            $routeResponse
        );
    }
    
    public function testGroupWithStaticUriMultiple()
    {
        $router = $this->createRouter('GET', 'admin/user/products');
        
        $router->group('admin/user', function(RouteGroupInterface $group) {
            
            $group->get('products', function() {
                return 'admin.products';
            });
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'admin.products',
            $routeResponse
        );
    }
    
    public function testGroupWithNamedUriPassesDataToGroupRoutes()
    {
        $router = $this->createRouter('GET', 'admin/john/products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.'.$user.'.products';
            });
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'admin.john.products',
            $routeResponse
        );
    }
    
    public function testGroupWithOptionalUri()
    {
        $router = $this->createRouter('GET', 'admin/products');
        
        $router->group('admin/{?locale}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($locale) {
                return 'admin.'.$locale.'.products';
            });
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'admin..products',
            $routeResponse
        );
    }
    
    public function testGroupWithEmptyUri()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('', function(RouteGroupInterface $group) {
            
            $group->get('products', function() {
                return 'products';
            })->name('products');
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('products', $routeResponse);
        
        $this->assertSame(
            'https://example.com/products',
            (string) $router->url('products')
        );
    }
    
    public function testThatGroupUriGetsAppendedToUrl()
    {
        $router = $this->createRouter('GET', 'admin/products');
        
        $router->group('admin', function(RouteGroupInterface $group) {
            
            $group->get('products', function() {
                return 'admin.products';
            })->name('admin.products');
        });
        
        $this->assertSame(
            'https://example.com/admin/products',
            (string) $router->url('admin.products')
        );
    }
    
    public function testThatGroupUriGetsAppendedToUrlDynamic()
    {
        $router = $this->createRouter('GET', 'admin/user/products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
        });
        
        $this->assertSame(
            'https://example.com/admin/john/products',
            (string) $router->url('admin.products', ['user' => 'john'])
        );
    }
    
    public function testDomainMethod()
    {
        $router = $this->createRouter('GET', 'admin/user/products', 'sub.example.com');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
            
        })->domain('sub.example.com');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('admin.products', $routeResponse);
        
        $this->assertSame(
            'https://sub.example.com/admin/user/products',
            (string)$router->url('admin.products', ['user' => 'user'])
        );
    }
    
    public function testDomainMethodWithMultipleDomains()
    {
        $router = $this->createRouter('GET', 'admin/user/products', 'sub.example.com');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
            
        })->domain('example.com')->domain('sub.example.com');

        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('admin.products', $routeResponse);
        
        $this->assertSame(
            'https://sub.example.com/admin/name/products',
            (string)$router->url('admin.products', ['user' => 'name'])
        );
        
        $this->assertSame(
            'https://example.com/admin/name/products',
            (string)$router->url('admin.products', ['user' => 'name'])->domain('example.com')
        );        
    } 
    
    public function testDomainMethodRouteDomainHasPriority()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products')->domain('en.example.com');
            
        })->domain('sub.example.com');
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            'en.example.com',
            $route->getParameter('domain')
        );
    }
    
    public function testMiddlewareMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
        })->middleware(Middleware::class, AnotherMiddleware::class);
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            [
                Middleware::class,
                AnotherMiddleware::class,
            ],
            $route->getParameter('middleware'),
        );
    }
    
    public function testMiddlewareMethodRouteHasPriority()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products')->middleware(Middleware::class);
            
        })->middleware(Middleware::class, AnotherMiddleware::class);
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            [
                Middleware::class,
            ],
            $route->getParameter('middleware'),
        );
    }
    
    public function testBaseUrlMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
        })->baseUrl('sub.example.com');
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            'sub.example.com',
            $route->getParameter('base_url'),
        );
    }
    
    public function testBaseUrlMethodRouteHasPriority()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products')->baseUrl('en.example.com');
            
        })->baseUrl('sub.example.com');
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            'en.example.com',
            $route->getParameter('base_url'),
        );
    }
    
    public function testParameterMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products');
        })->parameter('key', 'value');
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            'value',
            $route->getParameter('key'),
        );
    }
    
    public function testParameterMethodRouteHasPriority()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->group('admin/{user}', function(RouteGroupInterface $group) {
            
            $group->get('products', function($user) {
                return 'admin.products';
            })->name('admin.products')->parameter('key', 'foo');
            
        })->parameter('key', 'value');
                
        $route = $router->getRoute('admin.products');
        
        $this->assertSame(
            'foo',
            $route->getParameter('key'),
        );
    }
    
    public function testGroupWithinGroup()
    {
        $router = $this->createRouter('GET', 'admin/shop/products');
        
        $router->group('admin', function(RouteGroupInterface $group) {
            
            $group->group('shop', function(RouteGroupInterface $group) {
                
                $group->get('products', function($user) {
                    return 'admin.shop.products';
                })->name('admin.shop.products');
                
            });
        });
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'admin.shop.products',
            $routeResponse
        );
    }    
}