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
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->productRepository->getAllProduct($filters);
    }

    public function getBySlug(string $slug, array $field): Product
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
