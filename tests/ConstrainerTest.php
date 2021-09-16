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

namespace Tobento\Service\Routing\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\Constrainer\ConstrainerInterface;
use Tobento\Service\Routing\Constrainer\RuleInterface;
use Tobento\Service\Routing\Constrainer\AlphaRule;

/**
 * ConstrainerTest tests
 */
class ConstrainerTest extends TestCase
{   
    public function testRuleMethod()
    {
        $rule = (new Constrainer())->rule('slug');
        
        $this->assertInstanceOf(
            RuleInterface::class,
            $rule
        );
    }
    
    public function testAddRuleMethod()
    {
        $constrainer = (new Constrainer())->addRule('alpha', new AlphaRule());
        
        $this->assertInstanceOf(
            ConstrainerInterface::class,
            $constrainer
        );
    }
    
    public function testRegexMethod()
    {
        $regex = (new Constrainer())->regex(':alpha');
        
        $this->assertSame(
            '[a-zA-Z]+',
            $regex
        );
    }
    
    public function testRegexMethodReturnsRuleNameIfNotFound()
    {
        $regex = (new Constrainer())->regex(':inexistence');
        
        $this->assertSame(
            ':inexistence',
            $regex
        );
    }
    
    public function testRegexMethodReturnsNullIfRuleHasNoRegex()
    {
        $regex = (new Constrainer())->regex(':in:foo:bar');
        
        $this->assertSame(
            null,
            $regex
        );
    }
    
    public function testMatchesMethod()
    {
        $this->assertTrue(
            (new Constrainer())->matches(':alphaNum', 'foo')
        );
    }    
}