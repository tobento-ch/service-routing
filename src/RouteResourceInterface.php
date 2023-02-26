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

/**
 * RouteResourceInterface
 */
interface RouteResourceInterface
{
    /**
     * Set an action
     *    
     * @param string $action The action name such as 'index'
     * @param string $method The method such as 'GET'
     * @param string $uri The uri
     * @param array<string, mixed> $parameters Any parameters such as ['constraints' => ['id' => '/^[0-9]+$/']]
     * @return static $this
     */
    public function action(string $action, string $method, string $uri = '', array $parameters = []): static;
    
    /**
     * Set the actions to route only
     *    
     * @param array<int, string> $actions The actions ['create']
     * @return static $this
     */
    public function only(array $actions = []): static;

    /**
     * Set the actions to route except
     *    
     * @param array<int, string> $actions The actions ['create']
     * @return static $this
     */
    public function except(array $actions = []): static;
    
    /**
     * Set middleware
     *
     * @param array<int, string> $actions If empty, middleware for all actions
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(array $actions, mixed ...$middleware): static;
    
    /**
     * Set a domain.
     *
     * @param string $domain
     * @return static $this
     */
    public function domain(string ...$domain): static;
    
    /**
     * Set a base url for all actions.
     *    
     * @param string $baseUrl
     * @return static $this
     */
    public function baseUrl(string $baseUrl): static;
    
    /**
     * Add a parameter for an action.
     *
     * @param string $action The action name such as 'index'
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $action, string $name, mixed $value): static;
    
    /**
     * Add a shared parameter for all actions.
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function sharedParameter(string $name, mixed $value): static;
    
    /**
     * Get the routes.
     *
     * @return array<int, RouteInterface>
     */
    public function getRoutes(): array;
}