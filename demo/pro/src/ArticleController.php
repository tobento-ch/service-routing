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

use Tobento\Service\View\ViewInterface;

/**
 * ArticleController
 */
class ArticleController
{            
    /**
     * Create a new ArticleController
     *
     * @param ViewInterface $view
     * @param ArticleRepository $articleRepository
     */    
    public function __construct(
        protected ViewInterface $view,
        protected ArticleRepository $articleRepository
    ) {}

    /**
     * Home action
     *
     * @param null|string $locale
     * @return null|string
     */
    public function home(null|string $locale): null|string
    {        
        return $this->view->render(
            'articles',
            [
                'title' => 'Articles',
                'description' => '',
                'articles' => $this->articleRepository->getAll($locale),
            ]
        );
    } 
    
    /**
     * Show action
     *
     * @param string $slug
     * @param null|string $locale
     * @return null|string
     */
    public function show(string $slug, null|string $locale): null|string
    {
        $article = $this->articleRepository->getBySlug($slug, $locale);
        
        if (is_null($article)) {
            return null;
        }
        
        return $this->view->render(
            'article',
            [
                'title' => $article->title(),
                'description' => $article->description(),
            ]
        );
    }   
}