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
 * RouteInterface
 */
interface RouteInterface extends RouteI18MethodsInterface
{
    /**
     * Set a route name
     *    
     * @param string $name
     * @return static $this
     */
    public function name(string $name): static;
    
    /**
     * Set a route middleware
     *    
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(mixed ...$middleware): static;
    
    /**
     * Mark route as signed.
     *
     * @param string $name
     * @param bool $validate True if to validate signed route.
     * @return static $this
     */
    public function signed(string $name, bool $validate = true): static;
    
    /**
     * Add a route uri constraint
     *
     * @param string $key The constraint key such as 'id' 
     * @param mixed $constraint
     * @return static $this
     */
    public function where(string $key, mixed $constraint): static;
    
    /**
     * Set a route uri query constraint
     *
     * @param mixed $constraint
     * @return static $this
     */
    public function query(mixed $constraint): static;
    
    /**
     * Set a callback to check if a route matches
     *
     * @param callable $matches function(RouteInterface $route): null|RouteInterface { return null; }
     * @param null|string $key A unique key.
     * @return static $this
     */
    public function matches(callable $matches, null|string $key = null): static;
    
    /**
     * Set a base url for the given route
     *    
     * @param string $baseUrl
     * @return static $this
     */
    public function baseUrl(string $baseUrl): static;
    
    /**
     * Add a parameter.
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $name, mixed $value): static;
    
    /**
     * Get the method.
     *
     * @return string
     */    
    public function getMethod(): string;
    
    /**
     * Get the uri.
     *
     * @return string
     */    
    public function getUri(): string;
    
    /**
     * Get the handler.
     *
     * @return mixed
     */    
    public function getHandler(): mixed;   

    /**
     * Get the name if any.
     *
     * @return null|string
     */    
    public function getName(): null|string;
    
    /**
     * Get the parameters.
     *
     * @return array<string, mixed>
     */    
    public function getParameters(): array;
    
    /**
     * Returns true if the parameter exist, otherwise false.
     *
     * @param string $name
     * @return bool
     */    
    public function hasParameter(string $name): bool;
    
    /**
     * Get the parameter value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */    
    public function getParameter(string $name, mixed $default = null): mixed;
}