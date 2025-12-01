<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    private const DEFAULT_FIELDS = ['id', 'slug', 'name', 'thumbnail', 'tagline', 'created_at'];

    public function __construct(
        protected CategoryRepository $categoryRepository
    ) {}

    public function getAll(array $fields = [])
    {
        return $this->categoryRepository->getAllCategory(
            empty($fields) ? self::DEFAULT_FIELDS : $fields
        );
    }

    public function getBySlug(string $slug, array $fields = ['*'])
    {
        return $this->categoryRepository->getCategoryBySlug($slug, $fields);
    }

    public function getById(string $id, array $fields = ['*'])
    {
        return $this->categoryRepository->getCategoryById($id, $fields);
    }
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }
            $category = $this->categoryRepository->createCategory($data);

            // [Audit Log]
            Log::info("Category Created: {$category->name}", ['created_by' => Auth::id()]);

            return $category;
        });
    }

    public function update(string $slug, array $data): Category
    {
        return DB::transaction(function () use ($slug, $data) {
            $category = $this->categoryRepository->getCategoryBySlug($slug, ['*']);

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $this->deleteOldThumbnail($category);
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }
            $updatedCategory = $this->categoryRepository->updateCategory($category, $data);

            // [Audit Log]
            Log::info("Category Updated: {$updatedCategory->name}", ['id' => $updatedCategory->id]);

            return $updatedCategory;
        });
    }

    public function delete(string $slug)
    {
        return DB::transaction(function () use ($slug) {
            $category = $this->categoryRepository->getCategoryBySlug($slug, ['*']);

            if ($category->products()->exists()) {
                throw new Exception("Kategori tidak dapat dihapus karena masih memiliki produk terkait.");
            }

            $this->deleteOldThumbnail($category);
            $deleted = $this->categoryRepository->deleteCategory($category);

            if ($deleted) {
                Log::warning("Category Deleted: {$category->name}", ['deleted_by' => Auth::id()]);
            }

            return $deleted;
        });
    }

    private function deleteOldThumbnail(Category $category): void
    {
        if ($path = $category->getRawOriginal('thumbnail')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uploadThumbnail(UploadedFile $thumbnail)
    {
        return $thumbnail->store('categories', 'public');
    }
}
