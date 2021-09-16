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
 * ProductRepository
 */
class ProductRepository
{            
    /**
     * Create a new ProductRepository
     *
     * @param array<int, Product> $products
     */    
    public function __construct(
        protected array $products = [],
    ) {
        $this->addProduct(new Product(
            1,
            'product',
            'Product',
            'Product description',
        ));
        
        $this->addProduct(new Product(
            2,
            'another-product',
            'Another product',
            'Another product description',
        ));        
    }

    /**
     * Add an Product
     *
     * @param Product $product
     * @return static $this
     */
    public function addProduct(Product $product): static
    {
        $this->products[$product->id()] = $product;
        return $this;
    }
    
    /**
     * Get a product by id.
     *
     * @param string $slug
     * @return null|Article
     */
    public function getById(int $id): null|Product
    {        
        return $this->products[$id] ?? null;
    }
    
    /**
     * Get all products
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->products;
    }    
}