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
 * Domain
 */
class Domain implements DomainInterface
{
    /**
     * Create a new Domain
     *
     * @param string $key
     * @param string $domain
     * @param string $uri
     */
    public function __construct(
        protected string $key,
        protected string $domain,
        protected string $uri,
    ) {}
    
    /**
     * Returns the key.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->key;   
    }
    
    /**
     * Returns the domain.
     *
     * @return string
     */
    public function domain(): string
    {
        return $this->domain;   
    }
    
    /**
     * Returns the uri.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;   
    }
}