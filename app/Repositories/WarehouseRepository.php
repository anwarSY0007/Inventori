<?php

namespace App\Repositories;

use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WarehouseRepository
{
    /**
     * Get all warehouses with pagination
     */
    public function getAllWarehouse(array $field = ['*'], int $perPage = 25): LengthAwarePaginator
    {
        return Warehouse::select($field)
            ->with(['products.category'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get warehouse by slug
     */
    public function getWarehouseBySlug(string $slug, array $field = ['*']): Warehouse
    {
        return Warehouse::select($field)
            ->with(['products.category'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Get warehouse by ID (UUID)
     */
    public function getWarehouseById(string $id, array $field = ['*']): Warehouse
    {
        return Warehouse::select($field)
            ->with(['products.category'])
            ->findOrFail($id);
    }

    /**
     * Create new warehouse
     */
    public function createWarehouse(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    /**
     * Update warehouse
     */
    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);
        return $warehouse->fresh(['products.category']);
    }

    /**
     * Delete warehouse (soft delete)
     */
    public function deleteWarehouse(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    /**
     * Attach products to warehouse with stock
     */
    public function attachProducts(Warehouse $warehouse, array $products): void
    {
        // Format: ['product_id' => ['stock' => 100]]
        $warehouse->products()->attach($products);
    }

    /**
     * Sync products to warehouse with stock
     */
    public function syncProducts(Warehouse $warehouse, array $products): void
    {
        // Format: ['product_id' => ['stock' => 100]]
        $warehouse->products()->sync($products);
    }

    /**
     * Update product stock in warehouse
     */
    public function updateProductStock(Warehouse $warehouse, string $productId, int $stock): void
    {
        $warehouse->products()->updateExistingPivot($productId, ['stock' => $stock]);
    }

    /**
     * Detach products from warehouse
     */
    public function detachProducts(Warehouse $warehouse, array $productIds = []): void
    {
        if (empty($productIds)) {
            $warehouse->products()->detach();
        } else {
            $warehouse->products()->detach($productIds);
        }
    }
}
