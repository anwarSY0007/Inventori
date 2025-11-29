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
        return [
            'mutations' => $this->stockMutationRepository->getAllMutation(
                array_merge($filters, ['product_id' => $productId])
            ),
            'summary'   => $this->stockMutationRepository->getStockSummary($productId, $filters),
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
        return $this->generateStockReport($filters, 'warehouse_id', $warehouseId);
    }

    /**
     * Get merchant stock report
     */
    public function getMerchantReport(string $merchantId, array $filters = []): array
    {
        return $this->generateStockReport($filters, 'merchant_id', $merchantId);
    }

    /**
     * Reusable logic for generating report
     */
    private function generateStockReport(array $filters, string $scopeKey, string $scopeId): array
    {
        $filters[$scopeKey] = $scopeId;
        $mutations = $this->stockMutationRepository->getAllMutation($filters);
        $items = $mutations instanceof LengthAwarePaginator
            ? collect($mutations->items())
            : $mutations;

        $productSummary = $items->groupBy('product_id')
            ->map(fn($group) => $this->calculateProductStats($group))
            ->values();

        return [
            $scopeKey         => $scopeId,
            'products'        => $productSummary,
            'total_products'  => $productSummary->count(),
            // Menggunakan total() dari paginator jika ada, atau count collection
            'total_mutations' => $mutations instanceof LengthAwarePaginator ? $mutations->total() : $mutations->count(),
        ];
    }

    /**
     * Extracted method untuk perhitungan statistik produk
     * Mengurangi kompleksitas cognitive (clean code)
     */
    private function calculateProductStats(Collection $items): array
    {
        $product = $items->first()->product;

        // Optimasi: Filter sekali saja
        $totalIn  = $items->where('type', 'in')->sum('amount');
        $totalOut = $items->where('type', 'out')->sum('amount');

        $lastMutation = $items->sortByDesc('created_at')->first();

        return [
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'total_in'      => $totalIn,
            'total_out'     => $totalOut,
            'net_change'    => $totalIn - $totalOut,
            'current_stock' => $lastMutation->current_stock ?? 0,
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
        return $this->getCurrentStock($productId, $warehouseId, $merchantId) >= $requiredAmount;
    }
}
