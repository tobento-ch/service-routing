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
 * RouteDispatcherInterface
 */
interface RouteDispatcherInterface
{
    /**
     * Dispatch the router routes.
     *
     * @param RouterInterface $router
     *
     * @throws RouteNotFoundException
     * @throws InvalidSignatureException
     * @throws TranslationException
     *
     * @return RouteInterface The matched route.
     */    
    public function dispatch(RouterInterface $router): RouteInterface;
}