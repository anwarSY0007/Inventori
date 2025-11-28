<?php

namespace App\Services;

use App\Models\Merchant;
use App\Repositories\MerchantRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MerchantService
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    /**
     * Get all merchants
     */
    public function getAll(array $field = []): LengthAwarePaginator
    {
        if (empty($field)) {
            $field = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'keeper_id', 'created_at'];
        }
        return $this->merchantRepository->getAllMerchant($field);
    }

    /**
     * Get merchant by slug
     */
    public function getBySlug(string $slug, array $field = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantBySlug($slug, $field);
    }

    /**
     * Get merchant by ID
     */
    public function getById(string $id, array $field = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantById($id, $field);
    }

    /**
     * Get merchant by keeper ID
     */
    public function getByKeeperId(string $keeperId, array $field = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantByKeeperId($keeperId, $field);
    }

    /**
     * Create new merchant
     */
    public function create(array $data): Merchant
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }

            $merchant = $this->merchantRepository->createMerchant($data);

            // Attach products if provided
            if (isset($data['products']) && is_array($data['products'])) {
                $this->merchantRepository->attachProducts($merchant, $data['products']);
            }

            return $merchant;
        });
    }

    /**
     * Update merchant
     */
    public function update(string $slug, array $data): Merchant
    {
        return DB::transaction(function () use ($slug, $data) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            // Handle thumbnail update
            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $this->deleteOldThumbnail($merchant);
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }

            $merchant = $this->merchantRepository->updateMerchant($merchant, $data);

            // Sync products if provided
            if (isset($data['products']) && is_array($data['products'])) {
                $this->merchantRepository->syncProducts($merchant, $data['products']);
            }

            return $merchant;
        });
    }

    /**
     * Delete merchant
     */
    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            // Delete thumbnail
            $this->deleteOldThumbnail($merchant);

            // Detach all products before delete
            $this->merchantRepository->detachProducts($merchant);

            return $this->merchantRepository->deleteMerchant($merchant);
        });
    }

    /**
     * Add products to merchant
     */
    public function addProducts(string $slug, array $products): Merchant
    {
        return DB::transaction(function () use ($slug, $products) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            // Format: ['product_id' => ['stock' => 100]]
            $this->merchantRepository->attachProducts($merchant, $products);

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Update product stock in merchant
     */
    public function updateProductStock(string $slug, string $productId, int $stock): Merchant
    {
        return DB::transaction(function () use ($slug, $productId, $stock) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            $this->merchantRepository->updateProductStock($merchant, $productId, $stock);

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Remove products from merchant
     */
    public function removeProducts(string $slug, array $productIds = []): Merchant
    {
        return DB::transaction(function () use ($slug, $productIds) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            $this->merchantRepository->detachProducts($merchant, $productIds);

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Delete old thumbnail
     */
    private function deleteOldThumbnail(Merchant $merchant): void
    {
        if ($merchant->thumbnail && Storage::disk('public')->exists($merchant->getRawOriginal('thumbnail'))) {
            Storage::disk('public')->delete($merchant->getRawOriginal('thumbnail'));
        }
    }

    /**
     * Upload thumbnail
     */
    private function uploadThumbnail(UploadedFile $thumbnail): string
    {
        return $thumbnail->store('merchants', 'public');
    }
}
