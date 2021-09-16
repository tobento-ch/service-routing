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
 * Constrainer
 */
class Constrainer implements ConstrainerInterface
{    
    /**
     * Create a new Constrainer
     *
     * @param array<string, RuleInterface> $rules
     * @param string $delimiter
     */    
    public function __construct(
        protected array $rules = [],
        protected string $delimiter = ':',
    ) {
        // default rules.
        $this->addRule('alpha', new AlphaRule());
        $this->addRule('num', new NumRule());
        $this->addRule('alphaNum', new AlphaNumRule());
        $this->addRule('id', new IdRule());
        $this->addRule('in', new InRule());
    }

    /**
     * Create a rule.
     *
     * @param string $name
     * @return Rule
     */   
    public function rule(string $name): Rule
    {
        $rule = new Rule();
        $this->rules[$name] = $rule;
        return $rule;
    }
    
    /**
     * Add a rule.
     *
     * @param string $name
     * @param RuleInterface $rule
     * @return static $this
     */   
    public function addRule(string $name, RuleInterface $rule): static
    {
        $this->rules[$name] = $rule;
        
        return $this;
    }    
    
    /**
     * Returns a regex from a rule found by constraint or null.
     *
     * @param mixed $constraint The constraint such as '[a-z]+', ':alpha:2:4'
     * @return null|string '[a-z]+'
     */    
    public function regex(mixed $constraint): null|string
    {
        [$name, $params] = $this->parseConstraint($constraint);

        if (!isset($this->rules[$name])) {
            return $constraint;
        }
        
        return $this->rules[$name]->getRegex($params);
    }
    
    /**
     * Returns wheteher a rule found by constraint and matches the given criteria.
     *
     * @param mixed $constraint The constraint such as '[a-z]+', ':alpha:2:4'
     * @param string $value
     * @return bool
     */    
    public function matches(mixed $constraint, string $value): bool
    {
        [$name, $params] = $this->parseConstraint($constraint);
        
        if (!isset($this->rules[$name])) {
            return true;
        }
        
        return $this->rules[$name]->matching($value, $params);
    }    

    /**
     * Parses a string constraint to rule name and parameters.
     *
     * @param mixed $constraint ':alpha:3:5'
     * @return array<int, mixed> ['rulename', [parameters]]
     */    
    protected function parseConstraint(mixed $constraint): array
    {
        if (is_string($constraint)) {
            return $this->parseConstraintFromString($constraint);
        }
        
        if (!is_array($constraint)) {
            return [null, []];
        }
        
        return $this->parseConstraintFromArray($constraint);
    }
    
    /**
     * Parses a string constraint to rule name and parameters.
     *
     * @param string $constraint ':alpha:3:5'
     * @return array<int, mixed> ['rulename', [parameters]]
     */    
    protected function parseConstraintFromString(string $constraint): array
    {        
        if (! str_starts_with($constraint, $this->delimiter)) {
            return [null, []];
        }
        
        $constraint = ltrim($constraint, $this->delimiter);
        $params = explode($this->delimiter, $constraint);
        $name = array_shift($params);
        
        return [$name, $params];
    }
    
    /**
     * Parses an array constraint to rule name and parameters.
     *
     * @param array<mixed> $constraint ['rulename', 'param 1', 'param 2']
     * @return array<int, mixed> ['rulename', [parameters]]
     */    
    protected function parseConstraintFromArray(array $constraint): array
    {
        if (!isset($constraint[0]) || !is_string($constraint[0])) {
            return [null, []];
        }
        
        $name = $constraint[0];
        array_shift($constraint);
        
        return [$name, $constraint];
    }    
}