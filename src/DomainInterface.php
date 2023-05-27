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
 * DomainInterface
 */
interface DomainInterface
{
    /**
     * Returns the key.
     *
     * @return string
     */
    public function key(): string;
    
    /**
     * Returns the domain.
     *
     * @return string
     */
    public function domain(): string;
    
    /**
     * Returns the uri.
     *
     * @return string
     */
    public function uri(): string;
}