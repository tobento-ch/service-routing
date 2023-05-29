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
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\UrlGeneratorInterface;
use Tobento\Service\Routing\UrlException;

/**
 * UrlGeneratorTest tests
 */
class UrlGeneratorTest extends TestCase
{   
    protected function createUrlGenerator(): UrlGeneratorInterface {
        
        return new UrlGenerator(
            'https://example.com',
            'a-random-32-character-secret-signature-key',
        );
    }
        
    public function testUrl()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/blog',
            $g->generate('blog')
        );
    }
    
    public function testUrlWithParameters()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/blog/5/foo',
            $g->generate('blog/{id}/{slug}', ['id' => 5, 'slug' => 'foo'])
        );
    }
    
    public function testUrlWithOptionalParameters()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/en/blog/5/foo',
            $g->generate('{?locale}/blog/{id}/{slug}', ['locale' => 'en', 'id' => 5, 'slug' => 'foo'])
        );
    }
    
    public function testUrlWithOptionalParametersIfEmptySkipsSegment()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/blog/5/foo',
            $g->generate('{?locale}/blog/{id}/{slug}', ['locale' => '', 'id' => 5, 'slug' => 'foo'])
        );
        
        $this->assertSame(
            'https://example.com/blog/5/foo',
            $g->generate('blog/{?locale}/{id}/{slug}', ['locale' => '', 'id' => 5, 'slug' => 'foo'])
        );        
    }
    
    public function testUrlWithMissingParameterThrowsUrlException()
    {
        $this->expectException(UrlException::class);
        
        $g = $this->createUrlGenerator();
        
        $g->generate('blog/{id}/{slug}', ['id' => 5]);
    }
    
    public function testUrlAdditionParametersGetAddedToQueryParameters()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/blog/5?slug=foo&locale=de',
            $g->generate('blog/{id}', ['id' => 5, 'slug' => 'foo', 'locale' => 'de'])
        );
    }
    
    public function testUrlWildcardParameter()
    {
        $g = $this->createUrlGenerator();
        
        $this->assertSame(
            'https://example.com/blog/foo/bar',
            $g->generate('blog/{path*}', ['path' => 'foo/bar'])
        );
    }     
}