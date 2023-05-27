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
use Tobento\Service\Routing\Domain;
use Tobento\Service\Routing\DomainInterface;

/**
 * DomainTest
 */
class DomainTest extends TestCase
{
    public function testDomain()
    {
        $domain = new Domain(
            key: 'example.ch',
            domain: 'ch.localhost',
            uri: 'http://ch.localhost',
        );
        
        $this->assertInstanceof(DomainInterface::class, $domain);
        $this->assertSame('example.ch', $domain->key());
        $this->assertSame('ch.localhost', $domain->domain());
        $this->assertSame('http://ch.localhost', $domain->uri());
    }
}