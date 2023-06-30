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

use Stringable;

/**
 * UrlInterface
 */
interface UrlInterface extends Stringable
{
    /**
     * Get the generated url.
     *    
     * @return string
     */
    public function get(): string;
    
    /**
     * Returns a new instance with the specified domain.
     *    
     * @param string $domain
     * @return static
     */
    public function domain(string $domain): static;
    
    /**
     * Returns all domained urls.
     *
     * @return array
     */
    public function domained(): array;
    
    /**
     * Sign a url.
     *    
     * @param mixed $expiration
     * @param bool $withQuery
     * @return static $this
     */
    public function sign(mixed $expiration = null, bool $withQuery = false): static;

    /**
     * Has url for the given locale.
     *    
     * @param string $locale
     * @return bool
     */
    public function hasTranslation(string $locale): bool;
    
    /**
     * Get all translated urls for the given locales.
     *    
     * @param array<int, string> $locales ['de', 'en']
     * @param bool $withQuery
     * @return array<string, string> ['de' => 'url']
     */
    public function translated(array $locales = []): array;
    
    /**
     * Set the locale.
     *    
     * @param null|string $locale
     * @return static $this
     */
    public function locale(null|string $locale = null): static;
}