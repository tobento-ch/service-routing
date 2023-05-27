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
 * Domains
 */
class Domains implements DomainsInterface
{
    /**
     * @var array<string, DomainInterface> Indexed by key.
     */
    protected array $domains = [];
    
    /**
     * @var array<string, string>
     */
    protected array $domainToKey = [];
    
    /**
     * Create a new Domains
     *
     * @param DomainInterface ...$domains
     */
    public function __construct(
        DomainInterface ...$domains,
    ) {
        foreach($domains as $domain) {
            $this->add($domain);
        }
    }
    
    /**
     * Adds a domain.
     *
     * @param DomainInterface $domain
     * @return static $this
     */
    public function add(DomainInterface $domain): static
    {
        $this->domains[$domain->key()] = $domain;
        $this->domainToKey[$domain->domain()] = $domain->key();
        return $this;
    }

    /**
     * Returns true if domain by key or domain exists, otherwise false.
     *
     * @param string $keyOrDomain
     * @return bool
     */
    public function has(string $keyOrDomain): bool
    {
        return $this->get($keyOrDomain) ? true : false;
    }
    
    /**
     * Returns the domain by key or domain or null if not exists.
     *
     * @param string $keyOrDomain
     * @return null|DomainInterface
     */
    public function get(string $keyOrDomain): null|DomainInterface
    {
        if (isset($this->domains[$keyOrDomain])) {
            return $this->domains[$keyOrDomain];
        }
        
        if (isset($this->domainToKey[$keyOrDomain])) {
            return $this->domains[$this->domainToKey[$keyOrDomain]] ?? null;
        }
        
        return null;
    }
    
    /**
     * Returns all domains.
     *
     * @return array<string, DomainInterface>
     */
    public function all(): array
    {
        return $this->domains;
    }
    
    /**
     * Returns the domains.
     *
     * @return array<int, string>
     */
    public function domains(): array
    {
        return array_keys($this->domainToKey);
    }
}