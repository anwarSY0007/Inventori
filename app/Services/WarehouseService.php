<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\WarehouseRepository;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WarehouseService
{
    private const DEFAULT_FIELDS = ['id', 'slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'created_at'];

    public function __construct(
        protected WarehouseRepository $warehouseRepository
    ) {}

    /**
     * Get all warehouses
     */
    public function getAll(array $field = []): LengthAwarePaginator
    {
        return $this->warehouseRepository->getAllWarehouse(
            empty($field) ? self::DEFAULT_FIELDS : $field
        );
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

                // [Audit Log]
                Log::info("Warehouse Created: {$warehouse->name}", ['created_by' => Auth::id()]);

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

                if (isset($data['thumbnail']) && $data['thumbnail'] instanceof UploadedFile) {
                    $this->deleteOldThumbnail($warehouse);
                    $data['thumbnail'] = $this->uploadThumbnail($data['thumbnail']);
                }

                $updatedWarehouse = $this->warehouseRepository->updateWarehouse($warehouse, $data);

                // [Audit Log]
                Log::info("Warehouse Updated: {$updatedWarehouse->name}", ['id' => $updatedWarehouse->id]);

                return $updatedWarehouse;
            }
        );
    }

    /**
     * Delete warehouse
     */
    public function delete(string $slug): bool
    {
        return DB::transaction(function () use ($slug) {
            $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['*']);
            $hasStock = $warehouse->products()->wherePivot('stock', '>', 0)->exists();
            if ($hasStock) {
                throw new Exception("Gudang tidak dapat dihapus karena masih memiliki stok produk aktif.");
            }

            $this->deleteOldThumbnail($warehouse);
            $deleted = $this->warehouseRepository->deleteWarehouse($warehouse);

            if ($deleted) {
                Log::warning("Warehouse Deleted: {$warehouse->name}", ['deleted_by' => Auth::id()]);
            }

            return $deleted;
        });
    }

    /**
     * Add products to warehouse
     */
    public function addProducts(string $slug, array $products): Warehouse
    {
        return DB::transaction(function () use ($slug, $products) {
            $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['id', 'name']);
            $this->warehouseRepository->attachProducts($warehouse, $products);

            // [Audit Log]
            Log::info("Products Attached to Warehouse: {$warehouse->name}", [
                'count' => count($products),
                'user_id' => Auth::id()
            ]);

            return $warehouse->fresh(['products.category']);
        });
    }

    /**
     * Update product stock in warehouse
     */
    public function updateProductStock(string $slug, string $productId, int $stock): Warehouse
    {
        if ($stock < 0) {
            throw new Exception("Stok gudang tidak boleh kurang dari 0.");
        }

        $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['id', 'name']);
        $this->warehouseRepository->updateProductStock($warehouse, $productId, $stock);

        // [Audit Log]
        Log::info("Warehouse Stock Updated Manually", [
            'warehouse' => $warehouse->name,
            'product_id' => $productId,
            'new_stock' => $stock,
            'updated_by' => Auth::id()
        ]);

        return $warehouse->fresh(['products.category']);
    }

    /**
     * Remove products from warehouse
     */
    public function removeProducts(string $slug, array $productIds = []): Warehouse
    {
        return DB::transaction(
            function () use ($slug, $productIds) {
                $warehouse = $this->warehouseRepository->getWarehouseBySlug($slug, ['id', 'name']);

                $productsWithStock = $warehouse->products()
                    ->whereIn('products.id', $productIds)
                    ->wherePivot('stock', '>', 0)
                    ->get(['products.id', 'products.name']);

                if ($productsWithStock->isNotEmpty()) {
                    // Ambil nama-nama produknya biar user tau mana yang bermasalah
                    $names = $productsWithStock->pluck('name')->join(', ');

                    throw new Exception(
                        "Gagal menghapus produk. Produk berikut masih memiliki stok di gudang ini: [{$names}]. " .
                            "Harap kosongkan stok terlebih dahulu melalui Transfer atau Mutasi Keluar."
                    );
                }

                // Eksekusi hapus (Detach) jika semua stok sudah 0
                $this->warehouseRepository->detachProducts($warehouse, $productIds);

                Log::warning("Products Removed/Detached from Warehouse: {$warehouse->name}", [
                    'product_ids' => $productIds,
                    'user_id' => Auth::id()
                ]);

                return $warehouse->fresh(['products.category']);
            }
        );
    }

    /**
     * Delete old thumbnail
     */
    private function deleteOldThumbnail(Warehouse $warehouse)
    {
        if ($oldThumbnail = $warehouse->getRawOriginal('thumbnail')) {
            Storage::disk('public')->delete($oldThumbnail);
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
