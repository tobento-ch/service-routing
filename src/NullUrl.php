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

class NullUrl implements UrlInterface
{
    /**
     * Create a new instance
     *
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     */        
    public function __construct(
        protected string $name,
        protected array $parameters,
    ) {}

    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get the generated url.
     *
     * @return string
     */
    public function get(): string
    {        
        return '';
    }

    /**
     * Returns a new instance with the specified domain.
     *
     * @param string $domain
     * @return static
     */
    public function domain(string $domain): static
    {
        return clone $this;
    }
    
    /**
     * Returns all domained urls.
     *
     * @return array
     */
    public function domained(): array
    {
        return [];
    }
    
    /**
     * Sign a url.
     *    
     * @param mixed $expiration
     * @param bool $withQuery
     * @return static $this
     */
    public function sign(mixed $expiration = null, bool $withQuery = false): static
    {
        return $this;
    }

    /**
     * Has url for the given locale.
     *    
     * @param string $locale
     * @return bool
     */
    public function hasTranslation(string $locale): bool
    {
        return false;
    }
    
    /**
     * Get all translated urls for the given locales.
     *    
     * @param array<int, string> $locales ['de', 'en']
     * @return array<string, string> ['de' => 'url']
     */
    public function translated(array $locales = []): array
    {
        return [];
    }    
    
    /**
     * Set the locale.
     *    
     * @param null|string $locale
     * @return static $this
     */
    public function locale(null|string $locale = null): static
    {
        return $this;
    }    
    
    /**
     * To string
     *    
     * @return string
     */
    public function __toString(): string
    {
        return '';
    }
}