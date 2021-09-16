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

use Psr\Http\Message\ResponseInterface;

/**
 * RouteResponseParserInterface
 */
interface RouteResponseParserInterface
{        
    /**
     * Parses the route response data.
     *
     * @param ResponseInterface $response
     * @param mixed $data The route response data.
     * @return ResponseInterface
     */    
    public function parse(ResponseInterface $response, mixed $data): ResponseInterface;
}