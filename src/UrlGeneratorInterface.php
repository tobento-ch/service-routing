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

use Psr\Http\Message\ServerRequestInterface;

/**
 * UrlGeneratorInterface
 */
interface UrlGeneratorInterface
{
    /**
     * Generates the url.
     *
     * @param string $uri The route uri such as 'foo/{bar}'
     * @param array $parameters The paramters to build the url.
     * @param null|string $baseUrl
     * @return string The generated url.
     * @throws UrlException
     */    
    public function generate(string $uri, array $parameters = [], null|string $baseUrl = null): string;

    /**
     * Generates the signed url.
     *
     * @param string $uri The route uri such as 'foo/{bar}'
     * @param array $parameters The paramters to build the url.
     * @param mixed $expiration The expiration
     * @param bool $withQuery
     * @param null|string $baseUrl
     * @return string The generated url.
     */    
    public function generateSigned(
        string $uri,
        array $parameters = [],
        mixed $expiration = null,
        bool $withQuery = false,
        null|string $baseUrl = null,
    ): string;

    /**
     * Returns true if the given data is a valid signature, otherwise false.
     *
     * @param string $uri The route uri such as 'foo/{slug}'
     * @param string $uriRequest The request uri such as 'foo/slug?name=value'
     * @return bool True if valid, otherwise false
     */    
    public function hasValidSignature(string $uri, string $uriRequest): bool;
    
    /**
     * Set the base url.
     *
     * @param string $urlBase The url base.
     * @return void
     */    
    public function setUrlBase(string $urlBase): void;
    
    /**
     * Get the base url.
     *
     * @return string
     */    
    public function getUrlBase(): string;

    /**
     * Sets the base url for uris.
     *
     * @param array $baseUrls ['foo/{id}' => 'http://www.example.com/base']
     * @return void
     */    
    public function setBaseUrls(array $baseUrls): void;

    /**
     * Add a base url for an uri.
     *
     * @param string $uri The uri such as 'foo/{id}'
     * @param string $baseUrl The base url such as 'http://www.example.com/base'
     * @return void
     */    
    public function addBaseUrl(string $uri, string $baseUrl): void;

    /**
     * Get the base url for the given uri if any.
     *
     * @param string $uri The uri such as 'foo/{id}'
     * @return null|string
     */    
    public function getBaseUrl(string $uri): null|string;
    
    /**
     * Get the signature name.
     *
     * @return string The generated url.
     */    
    public function getSignatureName(): string;
    
    /**
     * Get the expires name.
     *
     * @return string The generated url.
     */    
    public function getExpiresName(): string;   
}