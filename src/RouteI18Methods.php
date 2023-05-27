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
 * RouteI18Methods
 */
trait RouteI18Methods
{
    /**
     * Add a route domain.
     *
     * @param string $domain
     * @param null|callable $route
     * @return static $this
     */
    public function domain(string $domain, null|callable $route = null): static
    {
        $this->parameters['domains'][$domain] = $route;
        return $this;
    }
    
    /**
     * Set the locale.
     *    
     * @param string $locale The default or current locale.
     * @return static $this
     */
    public function locale(string $locale): static
    {
        $this->parameters['locale'] = $locale;
        return $this;
    }

    /**
     * Set the locales.
     *    
     * @param array<int, string> $locales The supported locales
     * @return static $this
     */
    public function locales(array $locales): static
    {
        $this->parameters['locales'] = $locales;
        return $this;
    }
    
    /**
     * The locale to omit on uri.
     *
     * @param string $localeOmit
     * @return static $this
     */
    public function localeOmit(string $localeOmit): static
    {
        $this->parameters['locale_omit'] = $localeOmit;
        return $this;
    }
    
    /**
     * Set the locale name.
     *    
     * @param string $localeName The locale name in uri.
     * @return static $this
     */
    public function localeName(string $localeName): static
    {
        $this->parameters['locale_name'] = $localeName;
        return $this;
    }
    
    /**
     * Set the locale fallbacks. ['de' => 'en']
     *    
     * @param array<string, string> $localeFallbacks
     * @return static $this
     */
    public function localeFallbacks(array $localeFallbacks): static
    {
        $this->parameters['locale_fallbacks'] = $localeFallbacks;
        return $this;
    }
    
    /**
     * Translate an uri key.
     *    
     * @param string $key
     * @param array<string, string> $translations $translations
     * @return static $this
     */
    public function trans(string $key, array $translations): static
    {
        $this->parameters['trans'][$key] = $translations;
    }
}