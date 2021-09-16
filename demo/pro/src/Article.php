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
 * Article
 */
class Article
{            
    /**
     * Create a new Article
     *
     * @param string $id
     * @param string $locale
     * @param string $slug
     * @param string $title
     * @param string $description
     */    
    public function __construct(
        protected string $id,
        protected string $locale,
        protected string $slug,
        protected string $title,
        protected string $description = '',
    ) {}

    /**
     * Get the id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
    
    /**
     * Get the locale.
     *
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }
    
    /**
     * Get the slug.
     *
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    } 
    
    /**
     * Get the title.
     *
     * @return string
     */
    public function title(): string
    {        
        return $this->title;
    }
    
    /**
     * Get the description.
     *
     * @return string
     */
    public function description(): string
    {        
        return $this->description;
    }  
}