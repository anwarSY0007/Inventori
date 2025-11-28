<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function getAllCategory(array $field): LengthAwarePaginator
    {
        return Category::select($field)->latest()->paginate(25);
    }

    public function getCategoryBySlug(string $slug, array $field): Category
    {
        return Category::select($field)->where('slug', $slug)->firstOrFail();
    }

    public function getCategoryById(string $id, array $field): Category
    {
        return Category::select($field)->findOrFail($id);
    }

    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }
    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh();
    }
    public function deleteCategory(Category $category): bool
    {
        return $category->delete();
    }
}
