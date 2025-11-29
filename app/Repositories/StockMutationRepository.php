<?php

namespace App\Repositories;

use App\Models\StockMutation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockMutationRepository
{
    /**
     * Get all stock mutations with pagination
     */
    public function getAllMutation(array $filters = [], array $field = ['*'], int $perPage = 25): LengthAwarePaginator
    {
        $query = StockMutation::select($field)
            ->with(['product.category', 'warehouse', 'merchant', 'creator', 'reference'])
            ->latest();

        // Filter by product
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filter by warehouse
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        // Filter by merchant
        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        // Filter by type (in/out)
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Filter by reference type (Transaction, Purchase, Adjustment)
        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get mutation by ID
     */
    public function getMutationById(string $id, array $field = ['*']): StockMutation
    {
        return StockMutation::select($field)
            ->with(['product.category', 'warehouse', 'merchant', 'creator', 'reference'])
            ->findOrFail($id);
    }

    /**
     * Create new stock mutation
     */
    public function createMutation(array $data): StockMutation
    {
        return StockMutation::create($data);
    }

    /**
     * Get mutations by product
     */
    public function getByProduct(string $productId, int $limit = 10): Collection
    {
        return StockMutation::where('product_id', $productId)
            ->with(['warehouse', 'merchant', 'creator'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get mutations by reference (polymorphic)
     */
    public function getByReference(string $referenceType, string $referenceId): Collection
    {
        return StockMutation::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->with(['product', 'warehouse', 'merchant'])
            ->get();
    }

    /**
     * Get stock summary by product
     */
    public function getStockSummary(string $productId, array $filters = []): array
    {
        $query = StockMutation::where('product_id', $productId);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $mutations = $query->get();

        return [
            'total_in' => $mutations->where('type', 'in')->sum('amount'),
            'total_out' => $mutations->where('type', 'out')->sum('amount'),
            'net_change' => $mutations->where('type', 'in')->sum('amount') - $mutations->where('type', 'out')->sum('amount'),
            'mutation_count' => $mutations->count(),
        ];
    }

    /**
     * Get latest mutation for product at location
     */
    public function getLatestMutation(string $productId, ?string $warehouseId = null, ?string $merchantId = null): ?StockMutation
    {
        $query = StockMutation::where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->latest()->first();
    }
}
