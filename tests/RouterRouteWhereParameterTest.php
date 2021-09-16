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

/**
 * RouterRouteWhereParameterTest tests
 */
class RouterRouteWhereParameterTest extends TestCase
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
    
    public function testWhereParameterMatches()
    {
        $router = $this->createRouter('GET', 'blog/45');
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', '[0-9]+');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            '45',
            $routeResponse
        );
    }
    
    public function testMultipleWhereParameterMatches()
    {
        $router = $this->createRouter('GET', 'blog/45/foo');
        
        $router->get('blog/{id}/{name}', function($id, $name) {
            return $id.$name;
        })->where('id', '[0-9]+')->where('name', '[a-z]+');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            '45foo',
            $routeResponse
        );
    }    
    
    public function testWhereParameterNotMatch()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'blog/foo');
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', '[0-9]+');
        
        $matchedRoute = $router->dispatch();
    }

    public function testWhereArraySyntax()
    {        
        $router = $this->createRouter('GET', 'blog/foo');
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', ['in', 'foo', 'bar']);
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'foo',
            $routeResponse
        );
    }
 
    public function testRuleDoesNotExistFails()
    {
        $this->expectException(RouteNotFoundException::class);
        
        $router = $this->createRouter('GET', 'blog/lorem-ipsum');
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', ':foo');
        
        $matchedRoute = $router->dispatch();
    }
    
    public function testCustomRule()
    {        
        $router = $this->createRouter('GET', 'blog/lorem-ipsum');
        
        $router->getRouteDispatcher()
           ->rule('slug')
           ->regex('[a-z0-9-]+');
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', ':slug');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'lorem-ipsum',
            $routeResponse
        );
    }
    
    public function testCustomRuleWithRegexClosure()
    {        
        $router = $this->createRouter('GET', 'blog/lorem-ipsum');
        
        $router->getRouteDispatcher()
           ->rule('slug')
           ->regex(function(array $parameters): null|string {
               // build the regex based on the parameters
               return '[a-z0-9-]+';
           });
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', ':slug');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'lorem-ipsum',
            $routeResponse
        );
    }    
    
    public function testCustomRuleWithMatches()
    {        
        $router = $this->createRouter('GET', 'blog/lorem-ipsum');
        
        $router->getRouteDispatcher()
           ->rule('slug')
           ->matches(function(string $value, array $parameters): bool {
               return true;
           });
        
        $router->get('blog/{id}', function($id) {
            return $id;
        })->where('id', ':slug');
        
        $matchedRoute = $router->dispatch();
        $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
        
        $this->assertSame(
            'lorem-ipsum',
            $routeResponse
        );
    }     
    
    public function testAlphaNumRule()
    {        
        $routes = [
            [
                'requestUri' => 'blog/id45',
                'rule' => ':alphaNum',
                'result' => 'id45',
            ],
            [
                'requestUri' => 'blog/id4*5',
                'rule' => ':alphaNum',
                'result' => null,
            ],             
            [
                'requestUri' => 'blog/id45',
                'rule' => ':alphaNum:4',
                'result' => 'id45',
            ],
            [
                'requestUri' => 'blog/id45',
                'rule' => ':alphaNum:5',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/id45',
                'rule' => ':alphaNum:4:6',
                'result' => 'id45',
            ],
            [
                'requestUri' => 'blog/id4556744',
                'rule' => ':alphaNum:4:6',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/id4556744',
                'rule' => ':alphaNum:4:',
                'result' => 'id4556744',
            ],            
        ];
        
        foreach($routes as $route)
        {
            $router = $this->createRouter('GET', $route['requestUri']);

            $router->get('blog/{id}', function($id) {
                return $id;
            })->where('id', $route['rule']);
                        
            if (is_null($route['result'])) {
                $this->expectException(RouteNotFoundException::class);
                $matchedRoute = $router->dispatch();
            } else {
                $matchedRoute = $router->dispatch();
                $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
                $this->assertSame($route['result'], $routeResponse);
            }        
        }        
    }
    
    public function testAlphaRule()
    {        
        $routes = [
            [
                'requestUri' => 'blog/lorem',
                'rule' => ':alpha',
                'result' => 'lorem',
            ],
            [
                'requestUri' => 'blog/lore3m',
                'rule' => ':alpha',
                'result' => null,
            ],            
            [
                'requestUri' => 'blog/lorem',
                'rule' => ':alpha:5',
                'result' => 'lorem',
            ],
            [
                'requestUri' => 'blog/lorem',
                'rule' => ':alpha:6',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/lorem',
                'rule' => ':alpha:4:6',
                'result' => 'lorem',
            ],
            [
                'requestUri' => 'blog/loremipsum',
                'rule' => ':alpha:4:6',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/loremipsum',
                'rule' => ':alpha:4:',
                'result' => 'loremipsum',
            ],            
        ];
        
        foreach($routes as $route)
        {
            $router = $this->createRouter('GET', $route['requestUri']);

            $router->get('blog/{id}', function($id) {
                return $id;
            })->where('id', $route['rule']);
                        
            if (is_null($route['result'])) {
                $this->expectException(RouteNotFoundException::class);
                $matchedRoute = $router->dispatch();
            } else {
                $matchedRoute = $router->dispatch();
                $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
                $this->assertSame($route['result'], $routeResponse);
            }        
        }        
    }
    
    public function testNumRule()
    {        
        $routes = [
            [
                'requestUri' => 'blog/12345',
                'rule' => ':num',
                'result' => '12345',
            ],
            [
                'requestUri' => 'blog/1a2345',
                'rule' => ':num',
                'result' => null,
            ],            
            [
                'requestUri' => 'blog/12345',
                'rule' => ':num:5',
                'result' => '12345',
            ],
            [
                'requestUri' => 'blog/12345',
                'rule' => ':num:6',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/12345',
                'rule' => ':num:4:6',
                'result' => '12345',
            ],
            [
                'requestUri' => 'blog/1234567',
                'rule' => ':num:4:6',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/1234567',
                'rule' => ':num:4:',
                'result' => '1234567',
            ],            
        ];
        
        foreach($routes as $route)
        {
            $router = $this->createRouter('GET', $route['requestUri']);

            $router->get('blog/{id}', function($id) {
                return $id;
            })->where('id', $route['rule']);
                        
            if (is_null($route['result'])) {
                $this->expectException(RouteNotFoundException::class);
                $matchedRoute = $router->dispatch();
            } else {
                $matchedRoute = $router->dispatch();
                $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
                $this->assertSame($route['result'], $routeResponse);
            }        
        }        
    }
    
    public function testIdRule()
    {        
        $routes = [
            [
                'requestUri' => 'blog/10',
                'rule' => ':id',
                'result' => '10',
            ],
            [
                'requestUri' => 'blog/10foo',
                'rule' => ':id',
                'result' => null,
            ],            
            [
                'requestUri' => 'blog/10',
                'rule' => ':id:5',
                'result' => '10',
            ],
            [
                'requestUri' => 'blog/10',
                'rule' => ':id:11',
                'result' => null,
            ],
            [
                'requestUri' => 'blog/111',
                'rule' => ':id:11:3',
                'result' => '111',
            ],
            [
                'requestUri' => 'blog/111',
                'rule' => ':id:11:4',
                'result' => null,
            ],           
        ];
        
        foreach($routes as $route)
        {
            $router = $this->createRouter('GET', $route['requestUri']);

            $router->get('blog/{id}', function($id) {
                return $id;
            })->where('id', $route['rule']);
                        
            if (is_null($route['result'])) {
                $this->expectException(RouteNotFoundException::class);
                $matchedRoute = $router->dispatch();
            } else {
                $matchedRoute = $router->dispatch();
                $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
                $this->assertSame($route['result'], $routeResponse);
            }        
        }        
    }
    
    public function testInRule()
    {        
        $routes = [
            [
                'requestUri' => 'blog/foo',
                'rule' => ':in:foo:bar',
                'result' => 'foo',
            ],
            [
                'requestUri' => 'blog/bar',
                'rule' => ':in:foo:bar',
                'result' => 'bar',
            ],
            [
                'requestUri' => 'blog/baz',
                'rule' => ':in:foo:bar',
                'result' => null,
            ],
        ];
        
        foreach($routes as $route)
        {
            $router = $this->createRouter('GET', $route['requestUri']);

            $router->get('blog/{id}', function($id) {
                return $id;
            })->where('id', $route['rule']);
                        
            if (is_null($route['result'])) {
                $this->expectException(RouteNotFoundException::class);
                $matchedRoute = $router->dispatch();
            } else {
                $matchedRoute = $router->dispatch();
                $routeResponse = $router->getRouteHandler()->handle($matchedRoute);
                $this->assertSame($route['result'], $routeResponse);
            }        
        }        
    }    
}