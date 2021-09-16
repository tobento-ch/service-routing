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

namespace Tobento\Demo\Routing\Pro;

/**
 * ArticleRepository
 */
class ArticleRepository
{            
    /**
     * Create a new ArticleRepository
     *
     * @param array<int, Article> $articles
     * @param string $locale
     */    
    public function __construct(
        protected array $articles = [],
        protected string $locale = 'en',
    ) {
        $this->addArticle(new Article(
            'some-article',
            'en',
            'some-article',
            'Some article',
            'Some article description',
        ));
        
        $this->addArticle(new Article(
            'some-article',
            'de',
            'ein-artikel',
            'Ein Artikel',
            'Ein Artikel Beschreibung',
        ));
        
        $this->addArticle(new Article(
            'some-other-articles',
            'en',
            'some-other-articles',
            'Some other article',
            'Some other article description',
        ));
                
        $this->addArticle(new Article(
            'some-other-articles',
            'de',
            'ein-anderer-artikel',
            'Ein anderer Artikel',
            'Ein anderer Artikel Beschreibung',
        ));        
    }

    /**
     * Add an Article
     *
     * @param Article $article
     * @return static $this
     */
    public function addArticle(Article $article): static
    {
        $this->articles[] = $article;
        return $this;
    }
    
    /**
     * Get an article by slug and locale.
     *
     * @param string $slug
     * @param null|string $locale
     * @return null|Article
     */
    public function getBySlug(string $slug, null|string $locale = null): null|Article
    {
        $locale = $locale ?: $this->locale;
        
        $articles = array_filter(
            $this->articles,
            fn($a) => $a->slug() === $slug && $a->locale() === $locale
        );
        
        return $articles[array_key_first($articles)] ?? null;
    }
    
    /**
     * Get all articles by locale.
     *
     * @param null|string $locale
     * @return array
     */
    public function getAll(null|string $locale = null): array
    {
        $locale = $locale ?: $this->locale;
        
        return array_filter(
            $this->articles,
            fn($a) => $a->locale() === $locale
        );
    }
    
    /**
     * Get all slugs by id.
     *
     * @param string $id
     * @return array
     */
    public function getSlugsById(string $id): array
    {        
        $articles = array_filter(
            $this->articles,
            fn($a) => $a->id() === $id
        );
        
        $slugs = [];
        
        foreach($articles as $article)
        {
            $slugs[$article->locale()] = $article->slug();
        }
        
        return $slugs;
    }    
}