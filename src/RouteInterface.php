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
interface RouteInterface
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