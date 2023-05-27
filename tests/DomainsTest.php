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
use Tobento\Service\Routing\Domains;
use Tobento\Service\Routing\Domain;
use Tobento\Service\Routing\DomainsInterface;
use Tobento\Service\Routing\DomainInterface;

/**
 * DomainsTest
 */
class DomainsTest extends TestCase
{
    public function testDomains()
    {
        $domains = new Domains();
        
        $this->assertInstanceof(DomainsInterface::class, $domains);
    }
    
    public function testHasMethod()
    {
        $domains = new Domains(
            new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
            new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
        );
        
        $this->assertTrue($domains->has('example.ch'));
        $this->assertTrue($domains->has('ch.localhost'));
        $this->assertTrue($domains->has('example.de'));
        $this->assertTrue($domains->has('de.localhost'));        
        $this->assertFalse($domains->has('fr.localhost'));
    }
    
    public function testGetMethod()
    {
        $domains = new Domains(
            new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
            new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
        );
        
        $this->assertInstanceof(DomainInterface::class, $domains->get('example.ch'));
        $this->assertSame('ch.localhost', $domains->get('example.ch')->domain());
        $this->assertSame('ch.localhost', $domains->get('ch.localhost')->domain());
        $this->assertSame('de.localhost', $domains->get('example.de')->domain());
        $this->assertSame('de.localhost', $domains->get('de.localhost')->domain());
        $this->assertSame(null, $domains->get('fr.localhost'));
    }
    
    public function testAllMethod()
    {
        $domains = new Domains();
        $this->assertSame(0, count($domains->all()));
        
        $domains = new Domains(
            new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
            new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
        );
        
        $this->assertSame(2, count($domains->all()));
    }
    
    public function testDomainsMethod()
    {
        $domains = new Domains();
        
        $this->assertSame([], $domains->domains());
        
        $domains = new Domains(
            new Domain(key: 'example.ch', domain: 'ch.localhost', uri: 'http://ch.localhost'),
            new Domain(key: 'example.de', domain: 'de.localhost', uri: 'http://de.localhost'),
        );

        $this->assertSame(['ch.localhost', 'de.localhost'], $domains->domains());
    }
}