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

use Tobento\Service\Support\Arrayable;

/**
 * Route
 */
class Route implements RouteInterface, Arrayable
{    
    /**
     * @var array The route parameters.
     */
    protected array $parameters = []; 
    
    /**
     * Create a new Route
     *
     * @param UrlGeneratorInterface $urlGenerator,
     * @param string $method The method such as 'GET', 'GET|POST', '*'
     * @param string $uri The route uri such as 'foo/{id}'
     * @param mixed $handler The handler if route is matching.
     */        
    public function __construct(
        protected UrlGeneratorInterface $urlGenerator,
        protected string $method,
        protected string $uri,
        protected mixed $handler
    ) {}

    /**
     * Set a route name
     *    
     * @param string $name
     * @return static $this
     */
    public function name(string $name): static
    {        
        $this->parameters['name'] = $name;
        
        return $this;
    } 
    
    /**
     * Mark route as signed.
     *
     * @param string $name
     * @param bool $validate True if to validate signed route.
     * @return static $this
     */
    public function signed(string $name, bool $validate = true): static
    {
        $this->parameters['signed'] = $validate;
        
        $signatureName = $this->urlGenerator->getSignatureName();
        $expiresName = $this->urlGenerator->getExpiresName();
        
        // adjust uri for route match.
        $this->uri = $this->uri.'/{?'.$signatureName.'}/{?'.$expiresName.'}';
        
        $this->name($name);
        
        // update base url if set.
        if (isset($this->parameters['base_url'])) {
            $this->baseUrl($this->parameters['base_url']);
        }
        
        return $this;
    }    

    /**
     * Set a route middleware
     *    
     * @param mixed $middleware
     * @return static $this
     */
    public function middleware(mixed ...$middleware): static
    {
        if (isset($this->parameters['middleware']))
        {
            $this->parameters['middleware'] = array_merge($this->parameters['middleware'], $middleware);
        } else {
            $this->parameters['middleware'] = $middleware;
        }

        return $this;
    }

    /**
     * Add a route uri constraint
     *
     * @param string $key The constraint key such as 'id' 
     * @param mixed $constraint
     * @return static $this
     */
    public function where(string $key, mixed $constraint): static
    {        
        $this->parameters['constraints'][$key] = $constraint;
        return $this;
    }

    /**
     * Set a route uri query constraint
     *
     * @param mixed $constraint
     * @return static $this
     */
    public function query(mixed $constraint): static
    {        
        $this->parameters['query'] = $constraint;
        return $this;
    }
    
    /**
     * Set a route domain.
     *
     * @param string $domain
     * @return static $this
     */
    public function domain(string ...$domain): static
    {
        $this->parameters['domain'] = $domain;
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
        
        if (!isset($this->parameters['locales'])) {
            $this->locales([]); // support any locales.
        }
        
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
            
        $this->matches(function(RouteInterface $route) {
                        
            $localeName = $route->getParameter('locale_name', 'locale');
            $requestParams = $route->getParameter('request_parameters', []);
            $locale = $requestParams[$localeName] ?? null;
            
            // check for locale in slug.
            if (
                $route->hasParameter('locale_omit')
                && $locale === $route->getParameter('locale_omit')
            ) {
                return null;
            }
            
            if (!empty($locale)) {
                $route->parameter('locale', $locale);
            }
            
            if (! $route->hasParameter('locale') && $route->hasParameter('locale_omit')) {
                $route->parameter('locale', $route->getParameter('locale_omit'));
            }
                                    
            if (! $route->hasParameter('locale')) {
                throw new TranslationException($this, 'No locale detected for translation');
            }
            
            // update
            $requestParams[$localeName] = $route->getParameter('locale');
            $route->parameter('request_parameters', $requestParams);
                
            // check for locales.
            if (
                $route->hasParameter('locales')
                && !empty($route->getParameter('locales'))
                && is_array($route->getParameter('locales'))
                && !in_array($route->getParameter('locale'), $route->getParameter('locales'))
            ) {
                return null;
            }
            
            return $route;
        });
        
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
     * Set locale base urls for the given route.
     *    
     * @param array<string, string> $baseUrls ['en' => 'en.example.com']
     * @return static $this
     */
    public function localeBaseUrls(array $baseUrls): static
    {        
        $this->parameters['locale_base_urls'] = $baseUrls;
        
        return $this;
    }    
    
    /**
     * Translate an uri key.
     *    
     * @param string $key
     * @param array<string, string> $translations $translations
     * @return static $this
     */
    public function trans(
        string $key,
        array $translations,
    ): static {
        $this->parameters['trans'][$key] = $translations;
        
        if (!isset($this->parameters['locales'])) {
            $this->locales([]); // support any locales.
        }
        
        $this->matches(
            function(RouteInterface $route)
            use ($key, $translations)
        {
            $requestParams = $route->getParameter('request_parameters', []);
            $trans = $requestParams[$key] ?? null;
            $locale = $route->getParameter('locale');
            
            if (!isset($translations[$locale])) {
                
                $fallbacks = $route->getParameter('locale_fallbacks', []);
                
                if (
                    is_array($fallbacks)
                    && isset($fallbacks[$locale])
                    && isset($translations[$fallbacks[$locale]])
                ) {
                    return $route;                    
                }

                return null;
            }

            return $translations[$locale] === $trans ? $route : null;
        });
        
        return $this;
    }    

    /**
     * Set a callback to check if a route matches
     *
     * @param callable $matches function(RouteInterface $route): null|RouteInterface { return null; }
     * @return static $this
     */
    public function matches(callable $matches): static
    {        
        $this->parameters['matches'][] = $matches;
        return $this;
    }

    /**
     * Set a base url for the given route
     *    
     * @param string $baseUrl
     * @return static $this
     */
    public function baseUrl(string $baseUrl): static
    {        
        $this->parameters['base_url'] = $baseUrl;
                
        $this->urlGenerator->addBaseUrl($this->uri, $baseUrl);
        
        return $this;
    }
    
    /**
     * Add a parameter.
     *
     * @param string $name The name
     * @param mixed $value The value
     * @return static $this
     */
    public function parameter(string $name, mixed $value): static
    {        
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Get the method.
     *
     * @return string
     */    
    public function getMethod(): string
    {
        return $this->method;
    }
    
    /**
     * Get the uri.
     *
     * @return string
     */    
    public function getUri(): string
    {
        return $this->uri;
    }
    
    /**
     * Get the handler.
     *
     * @return mixed
     */    
    public function getHandler(): mixed
    {
        return $this->handler;
    }
    
    /**
     * Get the name if any.
     *
     * @return null|string
     */    
    public function getName(): null|string
    {
        return $this->parameters['name'] ?? null;
    }    
    
    /**
     * Get the parameters.
     *
     * @return array<string, mixed>
     */    
    public function getParameters(): array
    {
        return $this->parameters;
    }
    
    /**
     * Returns true if the parameter exist, otherwise false.
     *
     * @param string $name
     * @return bool
     */    
    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }
    
    /**
     * Get the parameter value.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */    
    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }    
    
    /**
     * Object to array
     *
     * @return array
     */    
    public function toArray(): array
    {
        $handlerName = '';
        
        if (is_string($this->handler)) {
            $handlerName = $this->handler;
        }
        
        if (is_object($this->handler)) {
            $handlerName = get_class($this->handler);
        }
        
        if (is_array($this->handler)) {
            $handlerName = $this->handler;
        }
        
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'handler' => $handlerName,
            'parameters' => $this->parameters,            
        ];
    }
}