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

/**
 * ConstrainerInterface
 */
interface ConstrainerInterface
{
    /**
     * Create a rule.
     *
     * @param string $name
     * @return Rule
     */   
    public function rule(string $name): Rule;
    
    /**
     * Add a rule.
     *
     * @param string $name
     * @param RuleInterface $rule
     * @return static $this
     */   
    public function addRule(string $name, RuleInterface $rule): static;  
    
    /**
     * Returns a regex from a rule found by constraint or null.
     *
     * @param mixed $constraint The constraint such as '[a-z]+', ':alpha:2:4'
     * @return null|string '[a-z]+'
     */    
    public function regex(mixed $constraint): null|string;
    
    /**
     * Returns wheteher a rule found by constraint and matches the given criteria.
     *
     * @param mixed $constraint The constraint such as '[a-z]+', ':alpha:2:4'
     * @param string $value
     * @return bool
     */    
    public function matches(mixed $constraint, string $value): bool;
}