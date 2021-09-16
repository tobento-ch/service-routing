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
 * Rule
 */
class Rule implements RuleInterface
{
    /**
     * Create a new Rule.
     *
     * @param null|string|Closure $regex
     * @param null|Closure $matches
     */    
    public function __construct(
        protected null|string|Closure $regex = null,
        protected null|Closure $matches = null,
    ) {}
    
    /**
     * Sets the regex.
     *
     * @param string|Closure $regex [a-z]+ or function(array $parameters): null|string {}
     * @return static $this
     */   
    public function regex(string|Closure $regex): static
    {
        $this->regex = $regex;
        return $this;
    }
    
    /**
     * Returns a regex or null if no regex.
     *
     * @param array<mixed> $parameters
     * @return null|string
     */   
    public function getRegex(array $parameters): null|string
    {
        if (is_null($this->regex)) {
            return null;
        }
        
        if (is_string($this->regex)) {
            return $this->regex;
        }
        
        return call_user_func_array($this->regex, [$parameters]);
    }

    /**
     * Sets a matches rule.
     *
     * @param Closure $callback function(string $value, array $parameters): bool {}
     * @return static $this
     */   
    public function matches(Closure $matches): static
    {
        $this->matches = $matches;
        return $this;
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
        if (is_null($this->matches)) {
            return true;
        }
        
        return call_user_func_array($this->matches, [$value, $parameters]);
    }
}