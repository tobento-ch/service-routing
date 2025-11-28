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
use Tobento\Service\Dater\Dater;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Routing\UrlGeneratorInterface;
use Tobento\Service\Routing\UrlException;

/**
 * UrlGeneratorTest tests
 */
class UrlGeneratorTest extends TestCase
{   
    protected function createUrlGenerator(): UrlGeneratorInterface
    {
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
    
    public function testWithEmptyBaseUrl()
    {
        $g = new UrlGenerator('', 'a-random-32-character-secret-signature-key');
        
        $this->assertSame('blog', $g->generate('blog'));
        $this->assertSame('/blog', $g->generate('/blog'));
    }
    
    public function testWithSlashedOnlyBaseUrl()
    {
        $g = new UrlGenerator('/', 'a-random-32-character-secret-signature-key');
        
        $this->assertSame('/blog', $g->generate('blog'));
        $this->assertSame('/blog', $g->generate('/blog'));
    }
    
    public function testWithSlashedEndBaseUrl()
    {
        $g = new UrlGenerator('foo/', 'a-random-32-character-secret-signature-key');
        
        $this->assertSame('foo/blog', $g->generate('blog'));
        $this->assertSame('foo/blog', $g->generate('/blog'));
    }
    
    public function testSignedWithoutExpires()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2']);
        
        $this->assertStringStartsWith('https://example.com/foo/2/baz/', $url);
        $this->assertSame(94, strlen($url));
        
        $this->assertTrue($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
    
    public function testSignedWithoutExpiresFailsIfInvalidSignature()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2']);
        
        $this->assertFalse($g->hasValidSignature(
            uri: 'foo/{bar}/baz',
            uriRequest: substr($url, strlen('https://example.com/')).'a1',
        ));
    }
    
    public function testSignedWithExpires()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], expiration: new Dater()->addDays(10));
        
        $this->assertStringStartsWith('https://example.com/foo/2/baz/', $url);
        $this->assertSame(105, strlen($url));
        
        $this->assertTrue($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
    
    public function testSignedWithExpiresFailsIfExpired()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], expiration: new Dater()->subDays(10));

        $this->assertFalse($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
    
    public function testSignedWithQueryAndWithoutExpires()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], withQuery: true);
        
        $this->assertStringStartsWith('https://example.com/foo/2/baz?signature=', $url);
        $this->assertSame(104, strlen($url));
        
        $this->assertTrue($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
    
    public function testSignedWithQueryAndWithoutExpiresFailsIfInvalidSignature()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], withQuery: true);
        
        $this->assertFalse($g->hasValidSignature(
            uri: 'foo/{bar}/baz',
            uriRequest: substr($url, strlen('https://example.com/')).'a1',
        ));
    }
    
    public function testSignedWithQueryAndWithExpires()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], expiration: new Dater()->addDays(10), withQuery: true);
        
        $this->assertStringStartsWith('https://example.com/foo/2/baz?expires=', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertSame(123, strlen($url));
        
        $this->assertTrue($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
    
    public function testSignedWithQueryAndWithExpiresFailsIfExpired()
    {
        $g = $this->createUrlGenerator();
        
        $url = $g->generateSigned(uri: 'foo/{bar}/baz', parameters: ['bar' => '2'], expiration: new Dater()->subDays(10), withQuery: true);

        $this->assertFalse($g->hasValidSignature(uri: 'foo/{bar}/baz', uriRequest: substr($url, strlen('https://example.com/'))));
    }
}