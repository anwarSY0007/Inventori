<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

            $product = $this->productRepository->createProduct($data);

            // [Audit Log]
            Log::info("Product Created: {$product->name}", [
                'product_id' => $product->id,
                'category_id' => $product->category_id,
                'price' => $product->price,
                'created_by' => Auth::id()
            ]);

            return $product;
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
            $updatedProduct = $this->productRepository->updateProduct($product, $data);

            // [Audit Log]
            Log::info("Product Updated: {$updatedProduct->name}", [
                'product_id' => $updatedProduct->id,
                'updated_by' => Auth::id()
            ]);

            return $updatedProduct;
        });
    }

    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $product = $this->productRepository->getProductBySlug($slug, ['*']);

            $warehouseStock = DB::table('warehouse_product')
                ->where('product_id', $product->id)
                ->sum('stock');

            if ($warehouseStock > 0) {
                throw new Exception("Produk tidak dapat dihapus karena masih ada stok ({$warehouseStock}) di Gudang.");
            }

            // [Validation] Cek apakah masih ada stok di Merchant
            $merchantStock = DB::table('merchant_product')
                ->where('product_id', $product->id)
                ->sum('stock');

            if ($merchantStock > 0) {
                throw new Exception("Produk tidak dapat dihapus karena masih ada stok ({$merchantStock}) tersebar di Merchant.");
            }

            $this->deleteOldThumbnail($product);

            $deleted = $this->productRepository->deleteProduct($product);

            if ($deleted) {
                Log::warning("Product Deleted: {$product->name}", [
                    'product_id' => $product->id,
                    'deleted_by' => Auth::id()
                ]);
            }

            return $deleted;
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
