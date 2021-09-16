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

/**
 * Handling the matched route events
 */
interface MatchedRouteHandlerInterface
{
    /**
     * Register a matched event listener.
     *
     * @param string $routeName The route name or null for any route.
     * @param callable $callable
     * @param int $priority The priority. Highest first.
     * @return void
     */    
    public function register(string $routeName, callable $callable, int $priority = 0): void;

    /**
     * Handles the matched route event.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return void
     */    
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): void;
}