<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\WarehouseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WarehouseService
{
    protected $warehouseRepository;

    public function __construct(WarehouseRepository $warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * Get all warehouses
     */
    public function getAll(array $field = []): LengthAwarePaginator
    {
        if (empty($field)) {
            $field = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'created_at'];
        }
        return $this->warehouseRepository->getAllWarehouse($field);
    }

    /**
     * Get warehouse by slug
     */
    public function getBySlug(string $slug, array $field = ['*']): Warehouse
    {
        return $this->warehouseRepository->getWarehouseBySlug($slug, $field);
    }

    /**
     * Get warehouse by ID
     */
    public function getById(string $id, array $field = ['*']): Warehouse
    {
        return $this->warehouseRepository->getWarehouseById($id, $field);
    }

    /**
     * Create new warehouse
     */
    public function create(array $data): Warehouse
    {
        return DB::transaction(
            function () use ($data) {
                if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                    $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
                }

                $warehouse = $this->warehouseRepository->createWarehouse($data);

                // Attach products if provided
                if (isset($data['products']) && is_array($data['products'])) {
                    $this->warehouseRepository->attachProducts($warehouse, $data['products']);
                }

                return $warehouse;
            }
        );
    }

    /**
     * Update warehouse
     */
    public function update(string $slug, array $data): Warehouse
    {
        return DB::transaction(
            function () use ($data, $slug) {
                $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);

                // Handle thumbnail update
                if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                    $this->deleteOldThumbnail($warehouse);
                    $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
                }

                $warehouse = $this->warehouseRepository->updateWarehouse($warehouse, $data);

                // Sync products if provided
                if (isset($data['products']) && is_array($data['products'])) {
                    $this->warehouseRepository->syncProducts($warehouse, $data['products']);
                }

                return $warehouse;
            }
        );
    }

    /**
     * Delete warehouse
     */
    public function delete(string $slug): bool
    {
        $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);

        // Delete thumbnail
        $this->deleteOldThumbnail($warehouse);

        // Detach all products before delete
        $this->warehouseRepository->detachProducts($warehouse);

        return $this->warehouseRepository->deleteWarehouse($warehouse);
    }

    /**
     * Add products to warehouse
     */
    public function addProducts(string $slug, array $products): Warehouse
    {
        $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);

        // Format: ['product_id' => ['stock' => 100]]
        $this->warehouseRepository->attachProducts($warehouse, $products);

        return $warehouse->fresh(['products.category']);
    }

    /**
     * Update product stock in warehouse
     */
    public function updateProductStock(string $slug, string $productId, int $stock): Warehouse
    {
        $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);

        $this->warehouseRepository->updateProductStock($warehouse, $productId, $stock);

        return $warehouse->fresh(['products.category']);
    }

    /**
     * Remove products from warehouse
     */
    public function removeProducts(string $slug, array $productIds = []): Warehouse
    {
        $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);

        $this->warehouseRepository->detachProducts($warehouse, $productIds);

        return $warehouse->fresh(['products.category']);
    }

    /**
     * Delete old thumbnail
     */
    private function deleteOldThumbnail(Warehouse $warehouse): void
    {
        if ($warehouse->thumbnail && Storage::disk('public')->exists($warehouse->getRawOriginal('thumbnail'))) {
            Storage::disk('public')->delete($warehouse->getRawOriginal('thumbnail'));
        }
    }

    /**
     * Upload thumbnail
     */
    private function uploadThumbnail(UploadedFile $thumbnail): string
    {
        return $thumbnail->store('warehouses', 'public');
    }
}
