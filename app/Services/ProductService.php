<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{

    private const DEFAULT_FIELDS = [
        'id',
        'slug',
        'category_id',
        'name',
        'price',
        'thumbnail',
        'is_popular',
        'created_at'
    ];

    public function __construct(
        protected ProductRepository $productRepository
    ) {}

    public function getAll(array $filters = [], array $fields = []): LengthAwarePaginator
    {
        $columns = empty($fields) ? self::DEFAULT_FIELDS : $fields;
        return $this->productRepository->getAllProduct($filters, $columns);
    }

    public function getBySlug(string $slug, array $field = ['*']): Product
    {
        return $this->productRepository->getProductBySlug($slug, $field);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }

            return $this->productRepository->createProduct($data);
        });
    }

    public function update(string $slug, array $data): Product
    {
        return DB::transaction(function () use ($slug, $data) {
            $product = $this->productRepository->getProductBySlug($slug, ['*']);

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $this->deleteOldThumbnail($product);
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }

            return $this->productRepository->updateProduct($product, $data);
        });
    }

    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $product = $this->productRepository->getProductBySlug($slug, ['*']);

            $this->deleteOldThumbnail($product);

            return $this->productRepository->deleteProduct($product);
        });
    }

    private function deleteOldThumbnail(Product $product): void
    {
        if ($product->thumbnail && Storage::disk('public')->exists($product->getRawOriginal('thumbnail'))) {
            Storage::disk('public')->delete($product->getRawOriginal('thumbnail'));
        }
    }

    private function uploadThumbnail(UploadedFile $thumbnail)
    {
        return $thumbnail->store('products', 'public');
    }
}
