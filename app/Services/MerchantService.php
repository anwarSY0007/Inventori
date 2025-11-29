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
    private const DEFAULT_FIELDS = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'keeper_id', 'created_at'];

    public function __construct(
        protected MerchantRepository $merchantRepository
    ) {}

    /**
     * Get all merchants
     */
    public function getAll(array $fields = []): LengthAwarePaginator
    {
        return $this->merchantRepository->getAllMerchant(
            empty($fields) ? self::DEFAULT_FIELDS : $fields
        );
    }

    /**
     * Get merchant by slug
     */
    public function getBySlug(string $slug, array $fields = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantBySlug($slug, $fields);
    }

    /**
     * Get merchant by ID
     */
    public function getById(string $id, array $fields = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantById($id, $fields);
    }

    /**
     * Get merchant by keeper ID
     */
    public function getByKeeperId(string $keeperId, array $fields = ['*']): Merchant
    {
        return $this->merchantRepository->getMerchantByKeeperId($keeperId, $fields);
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

            return $this->merchantRepository->createMerchant($data);
        });
    }

    /**
     * Update merchant
     */
    public function update(string $slug, array $data): Merchant
    {
        return DB::transaction(function () use ($slug, $data) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $this->deleteOldThumbnail($merchant);
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }

            return $this->merchantRepository->updateMerchant($merchant, $data);
        });
    }

    /**
     * Delete merchant
     */
    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['*']);
            $this->deleteOldThumbnail($merchant);
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
            $this->merchantRepository->attachProducts($merchant, $products);

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Update product stock in merchant
     */
    public function updateProductStock(string $slug, string $productId, int $stock): Merchant
    {
        $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['id']);
        $this->merchantRepository->updateProductStock($merchant, $productId, $stock);

        return $merchant->fresh(['keeper', 'products.category']);
    }

    /**
     * Remove products from merchant
     */
    public function removeProducts(string $slug, array $productIds = []): Merchant
    {
        $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['id']);
        $this->merchantRepository->detachProducts($merchant, $productIds);

        return $merchant->fresh(['keeper', 'products.category']);
    }

    /**
     * Delete old thumbnail
     */
    private function deleteOldThumbnail(Merchant $merchant): void
    {
        if ($thumbnail = $merchant->getRawOriginal('thumbnail')) {
            Storage::disk('public')->delete($thumbnail);
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
