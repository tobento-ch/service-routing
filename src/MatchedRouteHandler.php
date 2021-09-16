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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;

/**
 * MatchedRouteHandler
 */
class MatchedRouteHandler implements MatchedRouteHandlerInterface
{
    /**
     * @var array The registered matched event listeners.
     */    
    protected array $matched = [];

    /**
     * @var Autowire
     */    
    protected Autowire $autowire;
    
	/**
	 * Create a new MatchedRouteHandler
	 *
	 * @param ContainerInterface $container
	 */	
	public function __construct(
        protected ContainerInterface $container
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Register a matched event listener.
     *
     * @param string $routeName The route name or null for any route.
     * @param callable $callable
     * @param int $priority The priority. Highest first.
     * @return void
     */    
    public function register(string $routeName, callable $callable, int $priority = 0): void
    {
        $this->matched[$routeName][$priority][] = $callable;
    }

    /**
     * Handles the matched route event.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return void
     */    
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): void
    {        
        if (is_null($route->getName())) {
            return;
        }

        if (!isset($this->matched[$route->getName()])) {
            return;
        }

        $listeners = $this->matched[$route->getName()];
        
        unset($this->matched[$route->getName()]);
        
        // Sort by its priority.
        krsort($listeners);

        // Merge into one array depth.
        $listeners = call_user_func_array('array_merge', $listeners);

        foreach($listeners as $listener)
        {
            $this->autowire->call($listener);
        }        
    }
}