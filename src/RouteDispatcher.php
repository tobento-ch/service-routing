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

namespace Tobento\Service\Routing;

use Psr\Container\ContainerInterface;
use Tobento\Service\Routing\Constrainer\ConstrainerInterface;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\Constrainer\Rule;
use Tobento\Service\Routing\Constrainer\RuleInterface;
use Tobento\Service\Uri\UriRequest;
use Tobento\Service\Autowire\Autowire;
use Closure;

/**
 * Default route dispatcher.
 */
class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * @var Autowire
     */    
    protected Autowire $autowire;
    
    /**
     * @var array The methods.
     */    
    protected array $methods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    
    /**
     * @var null|UriRequest
     */    
    protected null|UriRequest $uriRequest = null;
    
    /**
     * Create a new RouteDispatcher
     *
     * @param ContainerInterface $container
     * @param null|ConstrainerInterface $constrainer
     */    
    public function __construct(
        protected ContainerInterface $container,
        protected null|ConstrainerInterface $constrainer = null,
    ){
        $this->autowire = new Autowire($container);
        $this->constrainer = $constrainer ?: new Constrainer();
    }

    /**
     * Dispatch the router routes.
     *
     * @param RouterInterface $router
     *
     * @throws RouteNotFoundException
     * @throws InvalidSignatureException
     * @throws TranslationException
     *
     * @return RouteInterface The matched route.
     */    
    public function dispatch(RouterInterface $router): RouteInterface
    {
        $requestData = $router->getRequestData();
        
        $this->uriRequest = null; // reset
            
        foreach($router->getRoutes() as $route)
        {
            if (!is_null($matchedRoute = $this->routeMatches($requestData, $route)))
            {
                if (
                    $matchedRoute->hasParameter('signed')
                    && ! $router->getUrlGenerator()->hasValidSignature($matchedRoute->getUri(), $requestData->uri())
                ) {            
                    throw new InvalidSignatureException($matchedRoute, 'Invalid Route Signature');
                }
                
                return $matchedRoute;
            }
        }

        throw new RouteNotFoundException('Route not found!');
    }
    
    /**
     * Add a constraint rule.
     *
     * @param string $name
     * @return null|Rule
     */    
    public function rule(string $name): null|Rule
    {
        return $this->constrainer?->rule($name);
    }
    
    /**
     * Add a constraint rule.
     *
     * @param string $name
     * @param RuleInterface $rule
     * @return static $this
     */   
    public function addRule(string $name, RuleInterface $rule): static
    {
        $this->constrainer?->addRule($name, $rule);
        
        return $this;
    }
    
    /**
     * Returns the route on match, otherwise null.
     *
     * @param RequestDataInterface $requestData
     * @param RouteInterface $route
     * @return null|RouteInterface The route on success, otherwise null
     */    
    protected function routeMatches(RequestDataInterface $requestData, RouteInterface $route): null|RouteInterface
    {
        // handle domain:
        if (!empty($route->getParameter('domains'))) {
            
            if (is_null($requestData->domain())) {
                return null;
            }
            
            $route = $route->forDomain($requestData->domain());
        }
        
        // verify domain:
        if (
            is_string($route->getParameter('domain'))
            && $route->getParameter('domain') !== $requestData->domain()
        ) {
            return null;
        }

        if (! $route->hasParameter('query')) {
            $route->parameter('query', '*');
        }
        
        // parse method
        if ($route->getMethod() === '*') {
            $methods = $this->methods;
        } else {
            $methods = explode('|', strtoupper($route->getMethod()));
        }        
                
        // method check and update
        if (!in_array($requestData->method(), $methods)) {
            return null;
        }
        
        $route->parameter('request_method', $requestData->method());
        
        $this->uriRequest = $this->uriRequest ?: new UriRequest($requestData->uri());
            
        if ($this->uriRequest->hasQuery())
        {
            if ($route->getParameter('query') === null) {
                return null;
            } elseif ($route->getParameter('query') === '*') {
                // ignore, all chars allowed
            } else {
                if ($this->matchesQueryConstraint(
                    $route->getParameter('query'),
                    $this->uriRequest->query()->get()
                ) === false) {
                    return null;
                }            
            }
        }
        
        // do we have a match.
        [$matches, $params] = $this->matches(
            $this->uriRequest->path()->get(),
            $route->getUri(),
            $route->getParameter('constraints', [])
        );
        
        if ($matches === false) {
            return null;
        }
        
        // assign addition data
        $route->parameter('method', $route->getMethod());
        $route->parameter('uri', $route->getUri());
        $route->parameter('request_uri', $this->uriRequest->get());
        $route->parameter('request_parameters', $params);
        $route->parameter('request_domain', $requestData->domain());
        
        // check for "matches" parameter.
        if (is_array($route->getParameter('matches')))
        {
            foreach($route->getParameter('matches') as $callback)
            {
                $route = $this->autowire->call($callback, ['route' => $route]);
            
                if ($route === null) {
                    return null;
                }
            }
        }
        
        return $route;
    }

    /**
     * If route uri matches the http uri.
     *
     * @param string $requestUri
     * @param string $routeUri
     * @param array<mixed> $constraints The constraints
     * @return array<mixed> [bool $matches, array $params]
     */    
    protected function matches(string $requestUri, string $routeUri, array $constraints): array
    {        
        // if all are static just check if same.
        if (! str_contains($routeUri, '{')) {
            return [$routeUri === $requestUri, []];
        }
        
        $regex = $this->buildRegexFromRouteUri($routeUri, $constraints);
        
        $matched = (bool) preg_match($regex, '/'.$requestUri, $matches);
                
        if ($matched === false) {
            return [false, []];
        }
        
        $params = $this->extractParametersFromMatches($matches);
        
        // check for constraint matching rule.
        if (!empty($params) && !is_null($this->constrainer))
        {
            foreach($params as $name => $value)
            {
                if (
                    isset($constraints[$name])
                    && $value !== ''
                    && ! $this->constrainer->matches($constraints[$name], $value))
                {
                    return [false, $params];
                }
            }
        }

        return [true, $params];
    }
    
    /**
     * Returns the build regex from the given route uri.
     *
     * @param string $uri The route uri '{foo}/bar'
     * @param array<mixed> $constraints The constraints
     * @return string
     */    
    protected function buildRegexFromRouteUri(string $uri, array $constraints): string
    {
        $regex = '';
        
        foreach(explode('/', $uri) as $segment)
        {
            $static = true;
            $rule = '[^/]+';
            $optional = '';
            $wildcard = false;
            
            if (substr($segment, 0, 1) === '{')
            {
                $static = false;
                $segment = ltrim($segment, '{');
                $segment = rtrim($segment, '}');
            }
            
            // optional
            if (substr($segment, 0, 1) === '?')
            {
                $segment = ltrim($segment, '?');
                $optional = '?';
            }
            
            // wildcard
            if (substr($segment, -1) === '*')
            {
                $segment = rtrim($segment, '*');
                $rule = '[^?]+';
                $wildcard = true;
            }
            
            // constraints regex.
            if (isset($constraints[$segment]))
            {
                $rule = $this->constrainer?->regex($constraints[$segment]) ?: $rule;
            }
            
            $name = '?<'.$segment.'>';
            
            if ($static)
            {
                $name = '';
                $rule = $segment;
            }            
            
            $regex .= '('.$name.'/'.$rule.')'.$optional;
            
            if ($wildcard)
            {
                break;
            }
        }

        return '#^'.$regex.'$#';        
    }

    /**
     * Extract Parameters from matches.
     *
     * @param array<int|string, string> $matches
     * @return array
     */    
    protected function extractParametersFromMatches(array $matches): array
    {        
        $filtered = [];
        
        foreach($matches as $name => $value)
        {
            if (!is_string($name)) {
                continue;
            }

            $value = ltrim($value, '/');
            
            $filtered[$name] = $value;
        }
        
        return $filtered;
    }   
    
    /**
     * Check if constraint matches query.
     *
     * @param mixed $constraint The constraint such as '[a-z]+'
     * @param string $value The uri request segment value.
     * @return bool True on success, otherwise false.
     */    
    protected function matchesQueryConstraint(mixed $constraint, string $value): bool
    {
        if (is_string($constraint) && !empty($constraint))
        {
            return (bool) preg_match($constraint, $value);
        }
        
        return false; 
    }
}