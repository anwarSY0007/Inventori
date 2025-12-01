<?php

namespace App\Services;

use App\Models\Merchant;
use App\Repositories\MerchantRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            $merchant = $this->merchantRepository->createMerchant($data);

            // [Audit Log]
            Log::info("Merchant Created: {$merchant->name}", [
                'merchant_id' => $merchant->id,
                'keeper_id' => $data['keeper_id']
            ]);

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

            if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                $this->deleteOldThumbnail($merchant);
                $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
            }
            $updatedMerchant = $this->merchantRepository->updateMerchant($merchant, $data);

            // [Audit Log]
            Log::info("Merchant Updated: {$updatedMerchant->name}", [
                'merchant_id' => $updatedMerchant->id
            ]);

            return $updatedMerchant;
        });
    }

    /**
     * Delete merchant
     */
    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug);

            $this->deleteOldThumbnail($merchant);
            $deleted = $this->merchantRepository->deleteMerchant($merchant);

            if ($deleted) {
                Log::warning("Merchant Deleted: {$merchant->name}", ['merchant_id' => $merchant->id]);
            }

            return $deleted;
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

            // [Audit Log]
            Log::info("Products Attached to Merchant: {$merchant->name}", [
                'merchant_id' => $merchant->id,
                'product_count' => count($products)
            ]);

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Update product stock in merchant
     */
    public function updateProductStock(string $slug, string $productId, int $stock): Merchant
    {
        if ($stock < 0) {
            throw new Exception("Stok tidak boleh kurang dari 0.");
        }

        $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['id', 'name']);

        $this->merchantRepository->updateProductStock($merchant, $productId, $stock);

        // [Audit Log] Penting untuk tracking perubahan stok manual
        Log::info("Merchant Stock Updated Manually", [
            'merchant' => $merchant->name,
            'product_id' => $productId,
            'new_stock' => $stock
        ]);

        return $merchant->fresh(['keeper', 'products.category']);
    }

    /**
     * Remove products from merchant
     */
    public function removeProducts(string $slug, array $productIds = []): Merchant
    {
        return DB::transaction(function () use ($slug, $productIds) {
            $merchant = $this->merchantRepository->getMerchantBySlug($slug, ['id', 'name']);

            $this->merchantRepository->detachProducts($merchant, $productIds);

            Log::info("Products Removed from Merchant: {$merchant->name}", [
                'merchant_id' => $merchant->id,
                'product_ids' => $productIds
            ]);

            return $merchant->fresh(['keeper', 'products.category']);
        });
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
