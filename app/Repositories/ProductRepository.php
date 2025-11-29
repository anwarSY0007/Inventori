<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllProduct(array $filters = [], array $columns = ['*'], int $perPage = 25): LengthAwarePaginator
    {
        $query = Product::with('category')->latest();

        // Filter: Category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter: Popular
        if (isset($filters['is_popular'])) {
            $query->where('is_popular', $filters['is_popular']);
        }

        // Filter: Search (Opsional, jika ada pencarian nama)
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage, $columns);
    }

    public function getProductBySlug(string $slug, array $columns = ['*']): Product
    {
        return Product::select($columns)->where('slug', $slug)->firstOrFail();
    }

    public function getProductById(string $id, array $columns = ['*']): Product
    {
        return Product::select($columns)->findOrFail($id);
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }
    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh(['category']);
    }
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }
}
