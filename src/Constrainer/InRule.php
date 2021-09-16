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

namespace Tobento\Service\Routing\Constrainer;

use Closure;

/**
 * InRule
 */
class InRule implements RuleInterface
{    
    /**
     * Returns a regex or null if no regex.
     *
     * @param array<mixed> $parameters
     * @return null|string
     */   
    public function getRegex(array $parameters): null|string
    {
        return null;
    }
    
    /**
     * Returns wheter the given value matches the parameters.
     *
     * @param string $value
     * @param array<mixed> $parameters The constraint parameters ['param1', ...]
     * @return bool
     */    
    public function matching(string $value, array $parameters): bool
    {
        return in_array($value, $parameters);
    }
}