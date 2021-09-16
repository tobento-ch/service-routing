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
 * Url
 */
class Url implements UrlInterface, Stringable
{
    /**
     * @var string The route uri
     */
    protected string $uri;
    
    /**
     * @var null|array<mixed>
     */
    protected null|array $sign = null;
    
    /**
     * @var null|string
     */
    protected null|string $locale = null;
    
    /**
     * @var null|string
     */
    protected null|string $baseUrl = null;    
    
    /**
     * Create a new Route
     *
     * @param RouterInterface $router
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     */        
    public function __construct(
        protected RouterInterface $router,
        protected string $name,
        protected array $parameters,
    ) {
        $route = $this->router->getRoute($name);
        
        if (is_null($route))
        {
            throw new UrlException(
                'Unable to generate url from undefined route name ['.$name.']'
            );
        }
        
        $this->uri = $route->getUri();
    }

    /**
     * Get the generated url.
     *    
     * @return string
     */
    public function get(): string
    {        
        return $this->__toString();
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
        $this->sign = [$expiration, $withQuery];
        
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
        return array_key_exists($locale, $this->translated());
    }
    
    /**
     * Get all translated urls for the given locales.
     *    
     * @param array<int, string> $locales ['de', 'en']
     * @param bool $withQuery
     * @return array<string, string> ['de' => 'url']
     */
    public function translated(array $locales = []): array
    {
        $route = $this->router->getRoute($this->name);
        
        if (empty($locales) && is_array($route->getParameter('locales'))) {
            $locales = $route->getParameter('locales');
        }
                
        $translations = $route->getParameter('trans');
        
        if (
            empty($locales)
            && is_array($translations)
        ) {
            $firstKey = array_key_first($translations);
            $firstTranslation = $translations[$firstKey] ?? [];
            $locales = array_keys($firstTranslation);
        }
        
        if (empty($locales) && is_string($route->getParameter('locale'))) {
            $locales[] = $route->getParameter('locale');
        }
        
        $translated = [];
        
        foreach($locales as $locale)
        {
            try {
                $translated[$locale] = (string)$this->locale($locale);
            } catch(TranslationException $e) {
                // ignore
            }
        }
        
        return $translated;
    }    
    
    /**
     * Set the locale.
     *    
     * @param null|string $locale
     * @return static $this
     */
    public function locale(null|string $locale = null): static
    {
        $route = $this->router->getRoute($this->name);
        $localeName = $route->getParameter('locale_name') ?? 'locale';
        $currentLocale = $route->getParameter('locale');
        $localeOmit = $route->getParameter('locale_omit') ?? null;
        $localeBaseUrls = $route->getParameter('locale_base_urls') ?? [];
        
        if (is_null($locale)) {
            $locale = $currentLocale ?: $localeOmit;
        }
        
        if (!empty($this->parameters[$localeName])) {
            $locale = $this->parameters[$localeName];
        }
        
        $this->locale = $locale;
        
        // store previous base url
        if (is_null($this->baseUrl)) {
            $this->baseUrl = $this->router->getUrlGenerator()->getBaseUrl($this->uri) 
                ?: $this->router->getUrlGenerator()->getUrlBase();
        }
        
        // do we have a locale url.
        if (is_array($localeBaseUrls) && isset($localeBaseUrls[$locale])) {
            $this->router->getUrlGenerator()->addBaseUrl($this->uri, $localeBaseUrls[$locale]);
        }
        
        // handle translations if any.
        if ($route->hasParameter('trans'))
        {
            foreach($route->getParameter('trans') as $key => $translations)
            {                    
                if (is_array($translations))
                {
                    if (!isset($translations[$locale]))
                    {
                        $fallbacks = $route->getParameter('locale_fallbacks');
                        
                        if (
                            is_array($fallbacks)
                            && isset($fallbacks[$locale])
                            && isset($translations[$fallbacks[$locale]])
                        ) {
                            $this->parameters[$key] ??= $translations[$fallbacks[$locale]];
                            continue;
                        }
                        
                        throw new TranslationException($route, 'Unable to generate url for locale.');
                    }
                    
                    $this->parameters[$key] ??= $translations[$locale];
                }
            }
        }
        
        // omit locale if set and matching.        
        if ($localeOmit && $localeOmit === $locale) {
            $locale = '';
        }
        
        if (
            str_contains($this->uri, '{'.$localeName.'}')
            || str_contains($this->uri, '{?'.$localeName.'}')
        ) {
            $this->parameters[$localeName] = $locale;
        }
        
        return $this;
    }    
    
    /**
     * To string
     *    
     * @return string
     */
    public function __toString(): string
    {
        if (is_null($this->locale)) {
            $route = $this->router->getRoute($this->name);
            $localeName = $route->getParameter('locale_name') ?? 'locale';
            $this->locale($this->parameters[$localeName] ?? null);    
        }
        
        if (!is_null($this->sign)) {
            [$expiration, $withQuery] = $this->sign;
            
            $url = $this->router->getUrlGenerator()->generateSigned(
                $this->uri,
                $this->parameters,
                $expiration,
                $withQuery
            );
                 
        } else {
            $url = $this->router->getUrlGenerator()->generate($this->uri, $this->parameters);    
        }
            
        if ($this->baseUrl) {
            $this->router->getUrlGenerator()->addBaseUrl($this->uri, $this->baseUrl);
        }
        
        // clear as non-translated parameters are prioritized.
        $this->parameters = [];
        $this->locale = null;
        
        return $url;
    }    
}