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
 * IdRule
 */
class IdRule implements RuleInterface
{
    /**
     * Create a new IdRule
     *
     * @param int $maxLen
     * @param int $minNumber
     */    
    public function __construct(
        protected int $maxLen = 21,
        protected int $minNumber = 1,
    ) {}
    
    /**
     * Returns a regex or null if no regex.
     *
     * @param array<mixed> $parameters
     * @return null|string
     */   
    public function getRegex(array $parameters): null|string
    {        
        if (isset($parameters[0]) && is_numeric($parameters[0]))
        {
            return '[0-9]{1,'.(string)$parameters[0].'}';
        }
 
        return '[0-9]{1,'.(string)$this->maxLen.'}';
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
        if (isset($parameters[1]) && is_numeric($parameters[1]))
        {
            $minNumber = (int)$parameters[1];
            
            return $value >= $minNumber;
        }
        
        return $value >= $this->minNumber;
    }
}