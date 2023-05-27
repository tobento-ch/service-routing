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
 * DomainInterface
 */
interface DomainsInterface
{
    /**
     * Adds a domain.
     *
     * @param DomainInterface $domain
     * @return static $this
     */
    public function add(DomainInterface $domain): static;

    /**
     * Returns true if domain by key or domain exists, otherwise false.
     *
     * @param string $keyOrDomain
     * @return bool
     */
    public function has(string $keyOrDomain): bool;
    
    /**
     * Returns the domain by key or domain or null if not exists.
     *
     * @param string $keyOrDomain
     * @return null|DomainInterface
     */
    public function get(string $keyOrDomain): null|DomainInterface;
    
    /**
     * Returns all domains.
     *
     * @return array<string, DomainInterface>
     */
    public function all(): array;
    
    /**
     * Returns the domains.
     *
     * @return array<int, string>
     */
    public function domains(): array;
}