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
 * RequestData
 */
class RequestData implements RequestDataInterface
{
    /**
     * Create a new RequestData
     *
     * @param string $method
     * @param string $uri
     * @param null|string $domain
     */        
    public function __construct(
        protected string $method,
        protected string $uri,
        protected null|string $domain = null
    ) {
        $this->method = strtoupper($method);
    }
    
    /**
     * Get the method
     *
     * @return string The method such as 'GET'
     */    
    public function method(): string
    {
        return $this->method;   
    }
    
    /**
     * Returns a new instance with the specified method.
     *
     * @param string $method
     * @return static
     */    
    public function withMethod(string $method): static
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;   
    }    
    
    /**
     * Get the uri
     *
     * @return string
     */    
    public function uri(): string
    {
        return $this->uri;   
    }
    
    /**
     * Returns a new instance with the specified uri.
     *
     * @param string $uri
     * @return static
     */    
    public function withUri(string $uri): static
    {
        $new = clone $this;
        $new->uri = $uri;
        return $new;   
    }    
 
    /**
     * Get the domain
     *
     * @return null|string
     */    
    public function domain(): null|string
    {        
        return $this->domain;
    }
    
    /**
     * Returns a new instance with the specified domain.
     *
     * @param null|string $uri
     * @return static
     */    
    public function withDomain(null|string $domain): static
    {
        $new = clone $this;
        $new->domain = $domain;
        return $new;   
    }     
}