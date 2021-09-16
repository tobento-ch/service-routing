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

use Exception;
use Throwable;

/**
 * InvalidSignatureException
 */
class InvalidSignatureException extends Exception
{
    /**
     * Create a new InvalidSignatureException
     *
     * @param null|RouteInterface $route
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected null|RouteInterface $route,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get the route.
     *
     * @return null|RouteInterface
     */
    public function route(): null|RouteInterface
    {
        return $this->route;
    }     
}