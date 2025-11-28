<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getAll(array $field = [])
    {
        if (empty($field)) {
            $field = ['id', 'slug', 'name', 'thumbnail', 'tagline', 'created_at'];
        }
        return $this->categoryRepository->getAllCategory($field);
    }

    public function getBySlug(string $slug, array $field)
    {
        return $this->categoryRepository->getCategoryBySlug($slug, $field);
    }

    public function getById(string $id, array $field)
    {
        return $this->categoryRepository->getCategoryById($id, $field ?? ['*']);
    }
    public function create(array $data)
    {
        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
        }
        return $this->categoryRepository->createCategory($data);
    }

    public function update(string $slug, array $data): Category
    {
        $category = $this->categoryRepository->getCategoryBySlug($slug, ['*']);

        if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
            $this->deleteOldThumbnail($category);
            $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
        }

        return $this->categoryRepository->updateCategory($category, $data);
    }

    public function delete(string $slug)
    {
        $category = $this->categoryRepository->getCategoryBySlug($slug, ['*']);

        // Hapus thumbnail sebelum delete data
        $this->deleteOldThumbnail($category);

        return $this->categoryRepository->deleteCategory($category);
    }

    private function deleteOldThumbnail(Category $category): void
    {
        if ($category->thumbnail && Storage::disk('public')->exists($category->getRawOriginal('thumbnail'))) {
            Storage::disk('public')->delete($category->getRawOriginal('thumbnail'));
        }
    }

    private function uploadThumbnail(UploadedFile $thumbnail)
    {
        return $thumbnail->store('categories', 'public');
    }
}
