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
 * NumRule
 */
class NumRule implements RuleInterface
{    
    /**
     * Returns a regex or null if no regex.
     *
     * @param array<mixed> $parameters
     * @return null|string
     */   
    public function getRegex(array $parameters): null|string
    {
        if (empty($parameters)) {
            return '[0-9]+';
        }
        
        // n{x}   :num:2   [2]      Matches any string that contains a sequence of X n's
        // n{x,y} :num:2:5 [2,5]    Matches any string that contains a sequence of X to Y n's
        // n{x,}  :num:2:  [2,null] Matches any string that contains a sequence of at least X n's
        
        $parameters = array_slice($parameters, 0, 2);
        
        return '[0-9]{'.implode(',', $parameters).'}';
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
        return true;
    }
}