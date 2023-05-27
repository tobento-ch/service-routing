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
     * @var RouteInterface $route
     */
    protected RouteInterface $route;
    
    /**
     * @var RouteInterface
     */
    protected RouteInterface $defaultRoute;
    
    /**
     * @var null|array<mixed>
     */
    protected null|array $sign = null;
    
    /**
     * @var null|string
     */
    protected null|string $locale = null;
    
    /**
     * Create a new Route
     *
     * @param RouterInterface $router
     * @param string $name The route name.
     * @param array $parameters The paramters to build the url.
     */        
    public function __construct(
        protected RouterInterface $router,
        string $name,
        protected array $parameters,
    ) {
        $route = $this->router->getRoute($name);
        
        if (is_null($route)) {
            throw new UrlException(
                'Unable to generate url from undefined route name ['.$name.']'
            );
        }
        
        $this->defaultRoute = $route;
        
        // handle domains:
        if (!empty($route->getParameter('domains'))) {
            
            $requestData = $router->getRequestData();
            
            if (!is_null($requestData->domain())) {
                $route = $route->forDomain($requestData->domain());
            }
        }
        
        $this->route = $route;
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
     * Returns a new instance with the specified domain.
     *    
     * @param string $domain
     * @return static
     */
    public function domain(string $domain): static
    {
        $new = clone $this;
        $new->route = $this->defaultRoute->forDomain($domain);
        return $new;
    }
    
    /**
     * Returns all domained urls.
     *
     * @return array
     */
    public function domained(): array
    {
        $domains = $this->route->getParameter('domains');
        
        if (!is_array($domains) || empty($domains)) {
            return [];
        }
        
        $domained = [];
        
        foreach(array_keys($domains) as $domain) {
            $domained[$domain] = (string)$this->domain($domain);
        }
        
        return $domained;
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
        if (empty($locales) && is_array($this->route->getParameter('locales'))) {
            $locales = $this->route->getParameter('locales');
        }
                
        $translations = $this->route->getParameter('trans');
        
        if (
            empty($locales)
            && is_array($translations)
        ) {
            $firstKey = array_key_first($translations);
            $firstTranslation = $translations[$firstKey] ?? [];
            $locales = array_keys($firstTranslation);
        }
        
        if (empty($locales) && is_string($this->route->getParameter('locale'))) {
            $locales[] = $this->route->getParameter('locale');
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
        $localeName = $this->route->getParameter('locale_name') ?? 'locale';
        $currentLocale = $this->route->getParameter('locale');
        $localeOmit = $this->route->getParameter('locale_omit') ?? null;
        
        if (is_null($locale)) {
            $locale = $currentLocale ?: $localeOmit;
        }
        
        if (!empty($this->parameters[$localeName])) {
            $locale = $this->parameters[$localeName];
        }
        
        $this->locale = $locale;
        
        // handle translations if any.
        if ($this->route->hasParameter('trans'))
        {
            foreach($this->route->getParameter('trans') as $key => $translations)
            {                    
                if (is_array($translations))
                {
                    if (!isset($translations[$locale]))
                    {
                        $fallbacks = $this->route->getParameter('locale_fallbacks');
                        
                        if (
                            is_array($fallbacks)
                            && isset($fallbacks[$locale])
                            && isset($translations[$fallbacks[$locale]])
                        ) {
                            $this->parameters[$key] ??= $translations[$fallbacks[$locale]];
                            continue;
                        }
                        
                        throw new TranslationException($this->route, 'Unable to generate url for locale.');
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
            str_contains($this->route->getUri(), '{'.$localeName.'}')
            || str_contains($this->route->getUri(), '{?'.$localeName.'}')
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
        // handle domain:
        $baseUrl = null;
        
        if (is_string($this->route->getParameter('domain'))) {

            if (is_string($this->route->getParameter('domain_uri'))) {
                $baseUrl = $this->route->getParameter('domain_uri');
            } else {
                $scheme = str_starts_with($this->router->getUrlGenerator()->getUrlBase(), 'https://')
                    ? 'https://'
                    : 'http://';

                $domain = $this->route->getParameter('domain');
                
                $baseUrl = $scheme.$domain;
            }
        }
        
        // handle locale:
        if (is_null($this->locale)) {
            $localeName = $this->route->getParameter('locale_name') ?? 'locale';
            $this->locale($this->parameters[$localeName] ?? null);    
        }
        
        // generate signed url if specified:
        if (!is_null($this->sign)) {
            [$expiration, $withQuery] = $this->sign;
            
            $url = $this->router->getUrlGenerator()->generateSigned(
                $this->route->getUri(),
                $this->parameters,
                $expiration,
                $withQuery,
                $baseUrl,
            );
        // otherwise generate url:
        } else {
            $url = $this->router->getUrlGenerator()->generate(
                $this->route->getUri(),
                $this->parameters,
                $baseUrl,
            );    
        }
        
        // clear as non-translated parameters are prioritized.
        $this->parameters = [];
        $this->locale = null;
        
        return $url;
    }
}