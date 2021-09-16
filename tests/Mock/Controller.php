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

namespace Tobento\Service\Routing\Test\Mock;

/**
 * Controller
 */
class Controller
{
    /**
     * Create a new Controller
     *
     * @param Bar $bar
     */    
    public function __construct(
        protected Bar $bar,
    ) {}
    
    /**
     * Home
     *
     * @param Foo $foo
     * @return string
     */
    public function home(Foo $foo): string
    {        
        return 'home';
    }
    
    /**
     * Home
     *
     * @param string $foo
     * @return string
     */
    public function homeWithBuildInParameter(string $foo): string
    {        
        return 'home';
    }    
}