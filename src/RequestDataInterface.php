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
 * RequestDataInterface
 */
interface RequestDataInterface
{    
    /**
     * Get the method
     *
     * @return string The method such as 'GET'
     */    
    public function method(): string;

    /**
     * Returns a new instance with the specified method.
     *
     * @param string $method
     * @return static
     */    
    public function withMethod(string $method): static;
    
    /**
     * Get the uri
     *
     * @return string
     */    
    public function uri(): string;    

    /**
     * Returns a new instance with the specified uri.
     *
     * @param string $uri
     * @return static
     */    
    public function withUri(string $uri): static;
    
    /**
     * Get the domain
     *
     * @return null|string
     */    
    public function domain(): null|string;
    
    /**
     * Returns a new instance with the specified domain.
     *
     * @param null|string $uri
     * @return static
     */    
    public function withDomain(null|string $domain): static;    
}