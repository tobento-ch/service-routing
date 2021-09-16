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
use JsonSerializable;
use ArrayObject;

/**
 * Default route response parser.
 */
class RouteResponseParser implements RouteResponseParserInterface
{        
    /**
     * Parses the route response data.
     *
     * @param ResponseInterface $response
     * @param mixed $data The route response data.
     * @return ResponseInterface
     */    
    public function parse(ResponseInterface $response, mixed $data): ResponseInterface
    {
        if ($data instanceof ResponseInterface)
        {
            return $data;
        }
        
        if ($this->isJsonable($data))
        {
            $response->getBody()->write($this->convertToJson($data));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if (is_string($data))
        {
            $response->getBody()->write($data);
            return $response;
        }        
        
        return $response->withStatus(404);
    }

    /**
     * Can the content be converted into JSON.
     *
     * @param mixed $data Any data
     * @return bool True is jsonable, otherwise false
     */
    protected function isJsonable(mixed $data): bool
    {
        return $data instanceof ArrayObject ||
               $data instanceof JsonSerializable ||
               is_array($data);
    }
    
    /**
     * Converts the data into JSON.
     *
     * @param mixed $data Any content
     * @return string JSON string
     */
    protected function convertToJson(mixed $data): string
    {
        return json_encode($data);
    }
}