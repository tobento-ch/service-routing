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
use Tobento\Service\Routing\RouteInterface;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\RouteGroupInterface;
use Tobento\Service\Routing\RouteNotFoundException;
use Tobento\Service\Routing\InvalidSignatureException;
use Tobento\Service\Routing\Test\Mock\ProductsResource;
use Tobento\Service\Routing\Test\Mock\Middleware;
use Tobento\Service\Routing\Test\Mock\AnotherMiddleware;

/**
 * RouterRouteResourceTest tests
 */
class RouterRouteResourceTest extends TestCase
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
    
    public function testResourceActions()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class);
        
        $actions = [
            [
                'uri' => 'products',
                'method' => 'GET',
                'controller_method' => 'index',
                'controller_response' => 'index',                
            ],
            [
                'uri' => 'products/create',
                'method' => 'GET',
                'controller_method' => 'create',
                'controller_response' => 'create',
            ],
            [
                'uri' => 'products',
                'method' => 'POST',
                'controller_method' => 'store',
                'controller_response' => 'store',
            ], 
            [
                'uri' => 'products/15',
                'method' => 'GET',
                'controller_method' => 'show',
                'controller_response' => 'show/15',
            ],
            [
                'uri' => 'products/15/edit',
                'method' => 'GET',
                'controller_method' => 'edit',
                'controller_response' => 'edit/15',
            ],
            [
                'uri' => 'products/15',
                'method' => 'PUT',
                'controller_method' => 'update',
                'controller_response' => 'update/15',
            ],
            [
                'uri' => 'products/15',
                'method' => 'PATCH',
                'controller_method' => 'update',
                'controller_response' => 'update/15',
            ],
            [
                'uri' => 'products/15',
                'method' => 'DELETE',
                'controller_method' => 'delete',
                'controller_response' => 'delete/15',
            ],             
        ];
        
        $routes = $router->getRoutes();
        
        foreach($actions as $action)
        {
            $router->clear();
            $router->addRoutes($routes);
            $router->setRequestData(
                $router->getRequestData()->withMethod($action['method'])->withUri($action['uri'])
            );
            
            $matchedRoute = $router->dispatch();
            $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
            
            $this->assertSame(
                $action['controller_response'],
                $routeResponse
            );            
        }
    }
    
    public function testOnlyMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->only(['index', 'show']);
                
        $routeNames = [];
        
        foreach($router->getRoutes() as $route)
        {
            $routeNames[] = $route->getName();    
        }
        
        $this->assertSame(
            ['products.index', 'products.show'],
            $routeNames
        );
    }
    
    public function testExceptMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->except(['delete']);
                
        $routeNames = [];
        
        foreach($router->getRoutes() as $route)
        {
            $routeNames[] = $route->getName();    
        }
        
        $this->assertSame(
            [
                'products.index',
                'products.create',
                'products.store',
                'products.show',
                'products.edit',
                'products.update',
            ],
            $routeNames
        );
    }
    
    public function testActionMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->action(
                   action: 'display', 
                   method: 'GET', 
                   uri: '/display/{id}',
                   parameters: ['constraints' => ['id' => '[0-9]+']],
               );
                
        $route = $router->getRoute('products.display');
        
        $this->assertSame(
            [
                'method' => 'GET',
                'uri' => 'products/display/{id}',
                'name' => 'products.display',
                'handler_action' => 'display',
            ],
            [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'handler_action' => $route->getHandler()[1],
            ]
        );
    }
    
    public function testThatActionOverwritesDefaultAction()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->action(
                   action: 'index',
                   method: 'GET', 
                   uri: '/index',
               );
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            [
                'method' => 'GET',
                'uri' => 'products/index',
                'name' => 'products.index',
                'handler_action' => 'index',
            ],
            [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'handler_action' => $route->getHandler()[1],
            ]
        );
    }
    
    public function testMiddlewareMethodWithEmptyActions()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->middleware(
                   [], // empty array for all actions
                   Middleware::class,
                   AnotherMiddleware::class,
               );
        
        $names = [
            'products.index',
            'products.create',
            'products.store',
            'products.show',
            'products.edit',
            'products.update',
            'products.delete',
        ];

        foreach($names as $name)
        {
            $route = $router->getRoute($name);

            $this->assertSame(
                [
                    Middleware::class,
                    AnotherMiddleware::class,
                ],
                $route->getParameter('middleware'),
            ); 
        }
    }
    
    public function testMiddlewareMethodWithSpecificActions()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->middleware(
                   ['index', 'show'], // empty array for all actions
                   Middleware::class,
                   AnotherMiddleware::class,
               );
        
        $names = [
            'products.index',
            'products.show',
        ];

        foreach($names as $name)
        {
            $route = $router->getRoute($name);

            $this->assertSame(
                [
                    Middleware::class,
                    AnotherMiddleware::class,
                ],
                $route->getParameter('middleware'),
            ); 
        }
        
        $names = [
            'products.create',
            'products.store',
            'products.edit',
            'products.update',
            'products.delete',
        ];

        foreach($names as $name)
        {
            $route = $router->getRoute($name);

            $this->assertSame(
                null,
                $route->getParameter('middleware'),
            ); 
        }        
    }
    
    public function testNameMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->name('foo');
        
        $route = $router->getRoute('foo.index');
        
        $this->assertInstanceOf(RouteInterface::class, $route);
    }    
    
    public function testWhereMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->where('[a-z0-9]+');
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            ['id' => '[a-z0-9]+'],
            $route->getParameter('constraints')
        );
    }    
    
    public function testParameterMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->parameter(
                   action: 'index',
                   name: 'foo',
                   value: 'bar',
               );
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            'bar',
            $route->getParameter('foo')
        );
        
        $route = $router->getRoute('products.show');
        
        $this->assertSame(
            null,
            $route->getParameter('foo')
        );
    }
    
    public function testSharedParameterMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->sharedParameter(
                   name: 'foo',
                   value: 'bar',
               );
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            'bar',
            $route->getParameter('foo')
        );
        
        $route = $router->getRoute('products.show');
        
        $this->assertSame(
            'bar',
            $route->getParameter('foo')
        );
    }
    
    public function testDomainMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
            ->domain('sub.example.com');
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            ['sub.example.com' => null],
            $route->getParameter('domains')
        );
    }
    
    public function testDomainMethodWithMultiple()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
            ->domain('example.com')
            ->domain('sub.example.com');
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            ['example.com' => null, 'sub.example.com' => null],
            $route->getParameter('domains')
        );
    }
    
    public function testBaseUrlMethod()
    {
        $router = $this->createRouter('GET', 'products');
        
        $router->resource('products', ProductsResource::class)
               ->baseUrl('sub.example.com');
                
        $route = $router->getRoute('products.index');
        
        $this->assertSame(
            'sub.example.com',
            $route->getParameter('base_url')
        );
    }
    
    public function testGroupResource()
    {
        $router = $this->createRouter('GET', 'admin/products');
        
        $router->group('admin', function(RouteGroupInterface $group) {
            
            $group->resource('products', ProductsResource::class);
            
        });
                
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'index',
            $routeResponse
        );
    }
    
    public function testGroupResourceThatGroupUriGetsAppendedToUrl()
    {
        $router = $this->createRouter('GET', 'admin/products');
        
        $router->group('admin/{locale}', function(RouteGroupInterface $group) {
            
            $group->resource('products', ProductsResource::class);
            
        });
                
        $this->assertSame(
            'https://example.com/admin/en/products',
            (string) $router->url('admin.locale.products.index', ['locale' => 'en'])
        );
    }

    public function testLocalizedRouting()
    {
        $router = $this->createRouter('GET', 'de/produkte/neu');
        
        $resource = $router->resource('{?locale}/{products}', ProductsResource::class)
            ->name('products')
            ->locales(['de', 'en'])
            ->localeOmit('en')
            ->localeFallbacks(['de' => 'en'])
            ->trans('create', ['de' => 'neu', 'en' => 'create'], 'create')
            ->trans('edit', ['de' => 'bearbeiten', 'en' => 'edit'], 'edit')
            ->trans('products', ['de' => 'produkte', 'en' => 'products']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('create', $routeResponse);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/produkte/neu',
                'en' => 'https://example.com/products/create',
            ],
            $router->url('products.create')->translated()
        );
        
        $this->assertSame(
            'https://example.com/products/5/edit',
            $router->url('products.edit', ['id' => '5'])->get()
        );
        
        $this->assertSame(
            'https://example.com/de/produkte/5/bearbeiten',
            $router->url('products.edit', ['id' => '5'])->locale('de')->get()
        );
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/produkte/5/bearbeiten',
                'en' => 'https://example.com/products/5/edit',
            ],
            $router->url('products.edit', ['id' => '5'])->translated()
        );
    }
    
    public function testLocalizedRoutingWithNewAction()
    {
        $router = $this->createRouter('GET', 'de/produkte/ansicht/5');
        
        $resource = $router->resource('{?locale}/{products}', ProductsResource::class)
            ->name('products')
            ->action(
                action: 'display', 
                method: 'GET', 
                uri: '/display/{id}',
                parameters: ['constraints' => ['id' => '[0-9]+']],
            )
            ->locales(['de', 'en'])
            ->localeOmit('en')
            ->localeFallbacks(['de' => 'en'])
            ->trans('display', ['de' => 'ansicht', 'en' => 'display'], action: 'display')
            ->trans('products', ['de' => 'produkte', 'en' => 'products']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame('display/5', $routeResponse);
        
        $this->assertSame(
            [
                'de' => 'https://example.com/de/produkte/ansicht/5',
                'en' => 'https://example.com/products/display/5',
            ],
            $router->url('products.display', ['id' => '5'])->translated()
        );
    }
}