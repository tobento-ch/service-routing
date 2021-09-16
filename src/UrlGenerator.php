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

use Tobento\Service\Uri\UriPath;
use Tobento\Service\Uri\UriRequest;
use Tobento\Service\Dater\DateFormatter;

/**
 * UrlGenerator
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var array Base url to uri. ['foo/{id}' => 'http://www.example.com/base']
     */    
    protected array $baseUrls = [];    
    
    /**
     * Create a new UrlGenerator.
     *
     * @param string $urlBase The url base such as https://example.com/base/
     * @param string $signatureKey The signature key
     * @param string $signatureName
     * @param string $expiresName
     */    
    public function __construct(
        protected string $urlBase,
        protected string $signatureKey,
        protected string $signatureName = 'signature',
        protected string $expiresName = 'expires',
    ) {}
    
    /**
     * Generates the url.
     *
     * @param string $uri The route uri such as 'foo/{bar}'
     * @param array $parameters The paramters to build the url.
     *
     * @throws UrlException
     *
     * @return string The generated url.
     */    
    public function generate(string $uri, array $parameters = []): string
    {
        $uriPath = new UriPath(str_replace('?', '!', $uri));
        
        // if uri is '', just return.
        if (empty($uriPath->getSegments())) {
            return $this->buildFullUrl($uri, '');
        }
        
        // replace parameters.
        $segments = [];
        $missingParameter = null;
        
        foreach($uriPath->getSegments() as $segment) {
            // check if uri is a defined parameter such as {id}.
            if (substr($segment, 0, 1) === '{') {
                
                $segment = ltrim($segment, '{');
                $segment = rtrim($segment, '}');
                $wildcard = false;
                //$optional = false;
                
                if (substr($segment, -1) === '*') {
                    $segment = substr($segment, 0, -1);
                    $wildcard = true;
                }
                
                if (substr($segment, 0, 1) === '!') {
                    $segment = ltrim($segment, '!');
                    //$optional = true;
                    $wildcard = true;
                }

                // do we have a matching parameter. If not, just return url base.
                if (!isset($parameters[$segment])) {
                    
                    if ($wildcard === false) {
                        $missingParameter = $segment;
                    }
                    
                    continue;
                }
                
                $name = $segment;
                $segment = $parameters[$segment];
                unset($parameters[$name]);
            }
            
            $segments[] = $segment;
        }

        $uriPath = $uriPath->withSegments($segments);
        
        if (!is_null($missingParameter)) {
            throw new UrlException(
                'Unable to generate url for ['.$uri.'] as missing parameter ['.$missingParameter.']'
            );
        }
        
        // add additional parameters to the query.
        if (!empty($parameters)) {
            $resolvedUri = (new UriRequest())->withPath($uriPath)->withQuery($parameters)->get();
        } else {
            $resolvedUri = $uriPath->get();
        }
        
        return $this->buildFullUrl($uri, $resolvedUri);
    }

    /**
     * Generates the signed url.
     *
     * @param string $uri The route uri such as 'foo/{bar}'
     * @param array $parameters The paramters to build the url.
     * @param mixed $expiration The expiration
     * @param bool $withQuery
     * @return string The generated url.
     */    
    public function generateSigned(
        string $uri,
        array $parameters = [],
        mixed $expiration = null,
        bool $withQuery = false
    ): string {
        
        $signatureUri = '/{?'.$this->signatureName.'}/{?'.$this->expiresName.'}';
        $uriOriginal = $uri;
        
        if ($withQuery) {      
            if (str_ends_with($uri, $signatureUri)) {
                $uri = str_replace($signatureUri, '', $uri);
            }
        } else {
            if (! str_ends_with($uri, $signatureUri)) {                
                $uri = $uri.$signatureUri;
            }
        }
        
        // adjust base url, as we changed uri.
        if (isset($this->baseUrls[$uriOriginal])) {
            $this->baseUrls[$uri] = $this->baseUrls[$uriOriginal];
        }
        
        // check if route uri has signature and expires defined.
        if ($expiration) {
            $df = new DateFormatter();
            $date = $df->toDateTime($expiration);
            $parameters[$this->expiresName] = $date->getTimestamp();
        }  

        $parameters[$this->signatureName] = '';

        $signature = hash_hmac('sha256', $this->generate($uri, $parameters), $this->signatureKey);
        
        $parameters[$this->signatureName] = $signature;
                
        return $this->generate($uri, $parameters);
    }

    /**
     * Returns true if the given data is a valid signature, otherwise false.
     *
     * @param string $uri The route uri such as 'foo/{slug}'
     * @param string $uriRequest The request uri such as 'foo/slug?name=value'
     * @return bool True if valid, otherwise false
     */    
    public function hasValidSignature(string $uri, string $uriRequest): bool
    {    
        $uriRequest = new UriRequest($uriRequest);
        
        $withQuery = false;
        
        // check if query param exist, otherwise take last two segments.
        if ($uriRequest->hasQuery()) {
            $parameters = $uriRequest->query()->getParameters();
            
            if (!empty($parameters[$this->signatureName])) {
                $withQuery = true;
            }
        }

        if ($withQuery) {
            
            $parameters = $uriRequest->query()->getParameters();
            $signature = $parameters[$this->signatureName] ?? '';
            $signature = is_string($signature) ? $signature : '';
            $expires = $parameters[$this->expiresName] ?? null;
            $expires = is_string($expires) ? $expires : null;
            $parameters[$this->signatureName] = '';
            $uriRequest = $uriRequest->withQuery($parameters);

        } else {
            $segmentsCount = count($uriRequest->path()->getSegments());
            $segments = $uriRequest->path()->getSegments();
            $expires = $uriRequest->path()->getSegment($segmentsCount, null);
            
            // determine if last segment is expires parameter.
            if (
                !is_null($expires)
                && !is_null((new DateFormatter())->toDateTime('@'.$expires, fallback: null))
            ) {
                $signature = $uriRequest->path()->getSegment($segmentsCount-1, '');
                $segments[$segmentsCount-2] = '';
            } else {
                $signature = $uriRequest->path()->getSegment($segmentsCount, '');
                $segments[$segmentsCount-1] = '';
            }
            
            $uriPath = $uriRequest->path()->withSegments($segments);           
            $uriRequest = $uriRequest->withPath($uriPath);         
        }
        
        $url = $this->buildFullUrl($uri, $uriRequest->get());
        
        // check for expiration
        if (!is_null($expires))
        {
            $df = new DateFormatter();
            
            if ($df->toDateTime('now')->getTimestamp() > $expires)
            {
                return false;
            }
        }
            
        $original = hash_hmac('sha256', $url, $this->signatureKey);
        
        return hash_equals($original, $signature);
    }
    
    /**
     * Set the base url.
     *
     * @param string $urlBase The url base.
     * @return void
     */    
    public function setUrlBase(string $urlBase): void
    {
        $this->urlBase = $urlBase;
    }
    
    /**
     * Get the base url.
     *
     * @return string
     */    
    public function getUrlBase(): string
    {
        return $this->urlBase;
    }

    /**
     * Sets the base url for uris.
     *
     * @param array $baseUrls ['foo/{id}' => 'http://www.example.com/base']
     * @return void
     */    
    public function setBaseUrls(array $baseUrls): void
    {
        $this->baseUrls = $baseUrls;
    }

    /**
     * Add a base url for an uri.
     *
     * @param string $uri The uri such as 'foo/{id}'
     * @param string $baseUrl The base url such as 'http://www.example.com/base'
     * @return void
     */    
    public function addBaseUrl(string $uri, string $baseUrl): void
    {
        $this->baseUrls[$uri] = $baseUrl;
    }
    
    /**
     * Get the base url for the given uri if any.
     *
     * @param string $uri The uri such as 'foo/{id}'
     * @return null|string
     */    
    public function getBaseUrl(string $uri): null|string
    {
        return $this->baseUrls[$uri] ?? null;
    }    
    
    /**
     * Get the signature name.
     *
     * @return string The generated url.
     */    
    public function getSignatureName(): string
    {
        return $this->signatureName;
    }
    
    /**
     * Get the expires name.
     *
     * @return string The generated url.
     */    
    public function getExpiresName(): string
    {
        return $this->expiresName;
    }    
        
    /**
     * Generates the url.
     *
     * @param string $uri The uri such as 'foo/{id}'
     * @param string $resolvedUri The resolved uri such as 'foo/5'
     * @return string The generated url.
     */    
    protected function buildFullUrl(string $uri, string $resolvedUri): string
    {        
        $url = $this->baseUrls[$uri] ?? $this->urlBase;
        
        //append $resolvedUri if not empty.
        if (!empty($resolvedUri)) {
            
            $url = rtrim($url, '/');
            $resolvedUri = '/'.ltrim($resolvedUri, '/');
            $url = $url.$resolvedUri;
        }
        
        return $url;
    }
}