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
 * RouteHandlerInterface.
 */
interface RouteHandlerInterface
{
    /**
     * Handles the route.
     *
     * @param RouteInterface $route
     * @param null|ServerRequestInterface $request
     * @return mixed The return value of the handler called.
     */    
    public function handle(RouteInterface $route, null|ServerRequestInterface $request = null): mixed;
}