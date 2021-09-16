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

use Psr\Http\Message\ServerRequestInterface;
use Tobento\Service\View\ViewInterface;

/**
 * ProductsResource
 */
class ProductsResource
{
    /**
     * Create a new ProductsResource
     *
     * @param ProductRepository $productRepository
     * @param ViewInterface $view
     */    
    public function __construct(
        protected ProductRepository $productRepository,
        protected ViewInterface $view,
    ) {}
    
    /**
     * Index action
     *
     * @return string
     */
    public function index(): string
    {
        return $this->view->render(
            'products/index',
            [
                'title' => 'Products',
                'description' => '',
                'products' => $this->productRepository->getAll(),
            ]
        );
    }
    
    /**
     * Create action
     *
     * @return string
     */
    public function create(): string
    {
        return $this->view->render(
            'products/create',
            [
                'title' => 'Product Create',
                'description' => '',
            ]
        );
    }
    
    /**
     * Store action
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    public function store(ServerRequestInterface $request): array
    {
        $input = $request->getParsedBody();

		if (!is_array($input)) {
            $input = [];
		}
        return is_array($input) ? $input : [];
    }
    
    /**
     * Show action
     *
     * @param int $id
     * @return string
     */
    public function show(int $id): string
    {
        return $this->view->render(
            'products/show',
            [
                'title' => 'Product Show',
                'description' => '',
                'product' => $this->productRepository->getById($id),
            ]
        );
    }

    /**
     * Edit action
     *
     * @param int $id
     * @return string
     */
    public function edit(int $id): string
    {
        return $this->view->render(
            'products/edit',
            [
                'title' => 'Product Edit',
                'description' => '',
                'product' => $this->productRepository->getById($id),
            ]
        );
    }
    
    /**
     * Update action
     *
     * @param ServerRequestInterface $request
     * @param int $id
     * @return array
     */
    public function update(ServerRequestInterface $request, int $id): array
    {
        $input = $request->getParsedBody();

		if (!is_array($input)) {
            $input = [];
		}
        return is_array($input) ? $input : [];
    }
    
    /**
     * Delete action
     *
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        return [
            'action' => 'delete',
            'id' => $id,
        ];
    }    
}