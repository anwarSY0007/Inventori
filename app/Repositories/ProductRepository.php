<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function getAllProduct(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Product::with('category')->latest();

        // Contoh filter sederhana jika diperlukan nanti
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_popular'])) {
            $query->where('is_popular', $filters['is_popular']);
        }

        return $query->paginate($perPage);
    }

    public function getProductBySlug(string $slug, array $field): Product
    {
        return Product::select($field)->where('slug', $slug)->firstOrFail();
    }

    public function getProductById(string $id, array $field): Product
    {
        return Product::select($field)->findOrFail($id);
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
