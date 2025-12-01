<?php

namespace App\Services;

use App\Repositories\MerchantRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MerchantProductService
{
    public function __construct(
        protected MerchantRepository $merchantRepository,
        protected StockMutationService $stockMutationService
    ) {}

    /**
     * Assign product dari warehouse ke merchant
     * Stock di warehouse akan berkurang, stock di merchant bertambah
     */
    public function assignProductToMerchant(array $data)
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = $data['warehouse_id'];
            $productId   = $data['product_id'];
            $amount      = $data['stock'];
            $userId      = Auth::id();

            // 1. Validasi Eksistensi di Gudang
            $existsInWarehouse = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->exists();

            if (!$existsInWarehouse) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di warehouse ini']
                ]);
            }

            // 2. Kurangi Stok Gudang (Atomic Decrement)
            $affected = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $amount)
                ->decrement('stock', $amount);

            if ($affected === 0) {
                $currentStock = DB::table('warehouse_product')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->value('stock');

                throw ValidationException::withMessages([
                    'stock' => ["Stok di warehouse tidak mencukupi. Tersedia: {$currentStock}, Diminta: {$amount}"]
                ]);
            }

            // 🔧 FIX: Ambil stok warehouse setelah decrement
            $warehouseStockAfter = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->value('stock');

            // 3. Tambah Stok Merchant (Update or Create)
            $merchant = $this->merchantRepository->getMerchantById($data['merchant_id'], ['id', 'name']);

            $existsInMerchant = DB::table('merchant_product')
                ->where('merchant_id', $merchant->id)
                ->where('product_id', $productId)
                ->exists();

            if ($existsInMerchant) {
                DB::table('merchant_product')
                    ->where('merchant_id', $merchant->id)
                    ->where('product_id', $productId)
                    ->increment('stock', $amount);
            } else {
                DB::table('merchant_product')->insert([
                    'merchant_id' => $merchant->id,
                    'product_id' => $productId,
                    'stock' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 🔧 FIX: Ambil stok merchant setelah increment
            $merchantStockAfter = DB::table('merchant_product')
                ->where('merchant_id', $merchant->id)
                ->where('product_id', $productId)
                ->value('stock');

            // 4. Catat Mutasi Keluar (Gudang)
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'amount' => $amount,
                'current_stock' => $warehouseStockAfter, // ✅ FIXED
                'note' => "Transfer ke Merchant: {$merchant->name}",
                'created_by' => $userId
            ]);

            // 5. Catat Mutasi Masuk (Merchant)
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $merchant->id,
                'type' => 'in',
                'amount' => $amount,
                'current_stock' => $merchantStockAfter, // ✅ FIXED
                'note' => "Transfer masuk dari Warehouse",
                'created_by' => $userId
            ]);

            Log::info("Stock Transfer: Warehouse -> Merchant", [
                'warehouse_id' => $warehouseId,
                'merchant_id' => $merchant->id,
                'product_id' => $productId,
                'amount' => $amount,
                'warehouse_stock_after' => $warehouseStockAfter,
                'merchant_stock_after' => $merchantStockAfter,
                'user_id' => $userId
            ]);

            return [
                'status' => 'success',
                'transferred_amount' => $amount,
                'warehouse_stock_after' => $warehouseStockAfter,
                'merchant_stock_after' => $merchantStockAfter,
            ];
        });
    }

    /**
     * 🆕 Return product dari merchant ke warehouse
     * Stock di merchant berkurang, stock di warehouse bertambah
     */
    public function returnProductToWarehouse(array $data)
    {
        return DB::transaction(function () use ($data) {
            $merchantId   = $data['merchant_id'];
            $warehouseId  = $data['warehouse_id'];
            $productId    = $data['product_id'];
            $amount       = $data['stock'];
            $userId       = Auth::id();

            // 1. Validasi stok merchant
            $merchantStock = DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->value('stock');

            if ($merchantStock === null) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di merchant ini']
                ]);
            }

            if ($merchantStock < $amount) {
                throw ValidationException::withMessages([
                    'stock' => ["Stok merchant tidak mencukupi. Tersedia: {$merchantStock}, Diminta: {$amount}"]
                ]);
            }

            // 2. Kurangi stok merchant (Atomic)
            $affected = DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $amount)
                ->decrement('stock', $amount);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok merchant tidak mencukupi untuk return ini']
                ]);
            }

            $merchantStockAfter = DB::table('merchant_product')
                ->where('merchant_id', $merchantId)
                ->where('product_id', $productId)
                ->value('stock');

            // 3. Tambah stok warehouse
            $warehouseExists = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->exists();

            if ($warehouseExists) {
                DB::table('warehouse_product')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->increment('stock', $amount);
            } else {
                DB::table('warehouse_product')->insert([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'stock' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $warehouseStockAfter = DB::table('warehouse_product')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->value('stock');

            // 4. Catat mutasi keluar (Merchant)
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $merchantId,
                'type' => 'out',
                'amount' => $amount,
                'current_stock' => $merchantStockAfter,
                'note' => "Return ke Warehouse",
                'created_by' => $userId
            ]);

            // 5. Catat mutasi masuk (Warehouse)
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'in',
                'amount' => $amount,
                'current_stock' => $warehouseStockAfter,
                'note' => "Return dari Merchant",
                'created_by' => $userId
            ]);

            Log::info("Stock Return: Merchant -> Warehouse", [
                'merchant_id' => $merchantId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'amount' => $amount,
                'merchant_stock_after' => $merchantStockAfter,
                'warehouse_stock_after' => $warehouseStockAfter,
                'user_id' => $userId
            ]);

            return [
                'status' => 'success',
                'returned_amount' => $amount,
                'merchant_stock_after' => $merchantStockAfter,
                'warehouse_stock_after' => $warehouseStockAfter,
            ];
        });
    }

    /**
     * 🆕 Transfer product antar merchant
     * Stock di source merchant berkurang, stock di target merchant bertambah
     */
    public function transferBetweenMerchants(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sourceMerchantId = $data['source_merchant_id'];
            $targetMerchantId = $data['target_merchant_id'];
            $productId        = $data['product_id'];
            $amount           = $data['stock'];
            $userId           = Auth::id();

            // 1. Validasi source merchant
            $sourceStock = DB::table('merchant_product')
                ->where('merchant_id', $sourceMerchantId)
                ->where('product_id', $productId)
                ->value('stock');

            if ($sourceStock === null) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di merchant asal']
                ]);
            }

            if ($sourceStock < $amount) {
                throw ValidationException::withMessages([
                    'stock' => ["Stok merchant asal tidak mencukupi. Tersedia: {$sourceStock}, Diminta: {$amount}"]
                ]);
            }

            // 2. Kurangi stok source merchant
            $affected = DB::table('merchant_product')
                ->where('merchant_id', $sourceMerchantId)
                ->where('product_id', $productId)
                ->where('stock', '>=', $amount)
                ->decrement('stock', $amount);

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok merchant asal tidak mencukupi']
                ]);
            }

            $sourceStockAfter = DB::table('merchant_product')
                ->where('merchant_id', $sourceMerchantId)
                ->where('product_id', $productId)
                ->value('stock');

            // 3. Tambah stok target merchant
            $targetExists = DB::table('merchant_product')
                ->where('merchant_id', $targetMerchantId)
                ->where('product_id', $productId)
                ->exists();

            if ($targetExists) {
                DB::table('merchant_product')
                    ->where('merchant_id', $targetMerchantId)
                    ->where('product_id', $productId)
                    ->increment('stock', $amount);
            } else {
                DB::table('merchant_product')->insert([
                    'merchant_id' => $targetMerchantId,
                    'product_id' => $productId,
                    'stock' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $targetStockAfter = DB::table('merchant_product')
                ->where('merchant_id', $targetMerchantId)
                ->where('product_id', $productId)
                ->value('stock');

            // 4. Catat mutasi
            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $sourceMerchantId,
                'type' => 'out',
                'amount' => $amount,
                'current_stock' => $sourceStockAfter,
                'note' => "Transfer ke Merchant lain",
                'created_by' => $userId
            ]);

            $this->stockMutationService->recordMutation([
                'product_id' => $productId,
                'merchant_id' => $targetMerchantId,
                'type' => 'in',
                'amount' => $amount,
                'current_stock' => $targetStockAfter,
                'note' => "Transfer masuk dari Merchant lain",
                'created_by' => $userId
            ]);

            Log::info("Stock Transfer: Merchant -> Merchant", [
                'source_merchant_id' => $sourceMerchantId,
                'target_merchant_id' => $targetMerchantId,
                'product_id' => $productId,
                'amount' => $amount,
                'user_id' => $userId
            ]);

            return [
                'status' => 'success',
                'transferred_amount' => $amount,
                'source_stock_after' => $sourceStockAfter,
                'target_stock_after' => $targetStockAfter,
            ];
        });
    }

    /**
     * Get stock movement history between warehouse and merchant
     */
    public function getStockMovementHistory(string $merchantId, string $productId): array
    {
        $merchant = $this->merchantRepository->getMerchantById($merchantId, ['id', 'name']);

        $stock = DB::table('merchant_product')
            ->where('merchant_id', $merchantId)
            ->where('product_id', $productId)
            ->value('stock');

        if (is_null($stock)) {
            throw ValidationException::withMessages([
                'product_id' => ['Produk tidak ditemukan di merchant ini']
            ]);
        }

        $history = $this->stockMutationService->getProductHistory($productId, [
            'merchant_id' => $merchantId
        ]);

        return [
            'merchant' => [
                'id'   => $merchant->id,
                'name' => $merchant->name,
            ],
            'product' => [
                'id'            => $productId,
                'current_stock' => $stock,
            ],
            'movements' => $history
        ];
    }
}
