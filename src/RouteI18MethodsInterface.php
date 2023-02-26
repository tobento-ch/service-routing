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
 * RouteI18MethodsInterface
 */
interface RouteI18MethodsInterface
{
    /**
     * Set a route domain.
     *
     * @param string $domain
     * @return static $this
     */
    public function domain(string ...$domain): static;
    
    /**
     * Set the locale.
     *    
     * @param string $locale The default or current locale.
     * @return static $this
     */
    public function locale(string $locale): static;
    
    /**
     * Set the locales.
     *    
     * @param array<int, string> $locales The supported locales
     * @return static $this
     */
    public function locales(array $locales): static;
    
    /**
     * The locale to omit on uri.
     *
     * @param string $localeOmit
     * @return static $this
     */
    public function localeOmit(string $localeOmit): static;
    
    /**
     * Set the locale name.
     *    
     * @param string $localeName The locale name in uri.
     * @return static $this
     */
    public function localeName(string $localeName): static;
    
    /**
     * Set the locale fallbacks. ['de' => 'en']
     *    
     * @param array<string, string> $localeFallbacks
     * @return static $this
     */
    public function localeFallbacks(array $localeFallbacks): static;
    
    /**
     * Set locale base urls for the given route.
     *    
     * @param array<string, string> $baseUrls ['en' => 'en.example.com']
     * @return static $this
     */
    public function localeBaseUrls(array $baseUrls): static;
    
    /**
     * Translate an uri key.
     *    
     * @param string $key
     * @param array<string, string> $translations $translations
     * @return static $this
     */
    public function trans(string $key, array $translations): static;
}