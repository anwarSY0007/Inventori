<?php

namespace App\Services;

use App\Models\StockMutation;
use App\Repositories\StockMutationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMutationService
{
    public function __construct(
        protected StockMutationRepository $stockMutationRepository
    ) {}

    /**
     * Get all stock mutations
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->stockMutationRepository->getAllMutation($filters);
    }

    /**
     * Get mutation by ID
     */
    public function getById(string $id): StockMutation
    {
        return $this->stockMutationRepository->getMutationById($id);
    }

    /**
     * Get mutations by product
     */
    public function getByProduct(string $productId, int $limit = 10): Collection
    {
        return $this->stockMutationRepository->getByProduct($productId, $limit);
    }

    /**
     * Get mutations by reference (e.g., all mutations for a specific transaction)
     */
    public function getByReference(string $referenceType, string $referenceId): Collection
    {
        return $this->stockMutationRepository->getByReference($referenceType, $referenceId);
    }

    /**
     * Record stock mutation
     * This is the main method called by other services
     */
    public function recordMutation(array $data): StockMutation
    {
        return DB::transaction(function () use ($data) {
            $mutationData = [
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'merchant_id' => $data['merchant_id'] ?? null,
                'type' => $data['type'], // 'in' or 'out'
                'amount' => $data['amount'],
                'current_stock' => $data['current_stock'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? Auth::id(),
            ];

            return $this->stockMutationRepository->createMutation($mutationData);
        });
    }

    /**
     * Record stock IN (masuk)
     * Digunakan saat: restocking, transfer masuk, pembatalan transaksi
     */
    public function recordStockIn(array $data): StockMutation
    {
        $data['type'] = 'in';
        return $this->recordMutation($data);
    }

    /**
     * Record stock OUT (keluar)
     * Digunakan saat: penjualan, transfer keluar, kerusakan
     */
    public function recordStockOut(array $data): StockMutation
    {
        $data['type'] = 'out';
        return $this->recordMutation($data);
    }

    /**
     * Get stock movement history for a product
     */
    public function getProductHistory(string $productId, array $filters = []): array
    {
        $mutations = $this->stockMutationRepository->getAllMutation(
            array_merge(['product_id' => $productId], $filters)
        );

        $summary = $this->stockMutationRepository->getStockSummary($productId, $filters);

        return [
            'mutations' => $mutations,
            'summary' => $summary,
        ];
    }

    /**
     * Get stock summary by product
     */
    public function getStockSummary(string $productId, array $filters = []): array
    {
        return $this->stockMutationRepository->getStockSummary($productId, $filters);
    }

    /**
     * Get warehouse stock report
     */
    public function getWarehouseReport(string $warehouseId, array $filters = []): array
    {
        $filters['warehouse_id'] = $warehouseId;

        $mutations = $this->stockMutationRepository->getAllMutation($filters);

        // Group by product
        $productSummary = $mutations->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            $totalIn = $items->where('type', 'in')->sum('amount');
            $totalOut = $items->where('type', 'out')->sum('amount');

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net_change' => $totalIn - $totalOut,
                'current_stock' => $items->sortByDesc('created_at')->first()->current_stock ?? 0,
            ];
        })->values();

        return [
            'warehouse_id' => $warehouseId,
            'products' => $productSummary,
            'total_products' => $productSummary->count(),
            'total_mutations' => $mutations->total(),
        ];
    }

    /**
     * Get merchant stock report
     */
    public function getMerchantReport(string $merchantId, array $filters = []): array
    {
        $filters['merchant_id'] = $merchantId;

        $mutations = $this->stockMutationRepository->getAllMutation($filters);

        // Group by product
        $productSummary = $mutations->groupBy('product_id')->map(function ($items) {
            $product = $items->first()->product;
            $totalIn = $items->where('type', 'in')->sum('amount');
            $totalOut = $items->where('type', 'out')->sum('amount');

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net_change' => $totalIn - $totalOut,
                'current_stock' => $items->sortByDesc('created_at')->first()->current_stock ?? 0,
            ];
        })->values();

        return [
            'merchant_id' => $merchantId,
            'products' => $productSummary,
            'total_products' => $productSummary->count(),
            'total_mutations' => $mutations->total(),
        ];
    }

    /**
     * Get current stock from latest mutation
     */
    public function getCurrentStock(string $productId, ?string $warehouseId = null, ?string $merchantId = null): int
    {
        $latestMutation = $this->stockMutationRepository->getLatestMutation(
            $productId,
            $warehouseId,
            $merchantId
        );

        return $latestMutation?->current_stock ?? 0;
    }

    /**
     * Validate stock before operation
     */
    public function validateStock(string $productId, int $requiredAmount, ?string $warehouseId = null, ?string $merchantId = null): bool
    {
        $currentStock = $this->getCurrentStock($productId, $warehouseId, $merchantId);
        return $currentStock >= $requiredAmount;
    }
}
