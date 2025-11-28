<?php

namespace App\Services;

use App\Models\Merchant;
use App\Repositories\MerchantRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchantProductService
{
    protected $merchantRepository;
    protected $warehouseRepository;

    public function __construct(MerchantRepository $merchantRepository, WarehouseRepository $warehouseRepository)
    {
        $this->merchantRepository = $merchantRepository;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * Assign product dari warehouse ke merchant
     * Stock di warehouse akan berkurang, stock di merchant bertambah
     */
    public function assignProductToMerchant(array $data): Merchant
    {
        return DB::transaction(function () use ($data) {
            $warehouse = $this->warehouseRepository->getWarehouseById($data['warehouse_id'], ['*']);
            $warehouseProduct = $warehouse->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if (!$warehouseProduct) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di warehouse ini']
                ]);
            }

            if ($warehouseProduct->pivot->stock < $data['stock']) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok di warehouse tidak mencukupi. Tersedia: ' . $warehouseProduct->pivot->stock]
                ]);
            }

            $merchant = $this->merchantRepository->getMerchantById($data['merchant_id'], ['*']);

            $existingProduct = $merchant->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if ($existingProduct) {
                $newStock = $existingProduct->pivot->stock + $data['stock'];
                $this->merchantRepository->updateProductStock(
                    $merchant,
                    $data['product_id'],
                    $newStock
                );
            } else {
                $this->merchantRepository->attachProducts($merchant, [
                    $data['product_id'] => ['stock' => $data['stock']]
                ]);
            }

            $newWarehouseStock = $warehouseProduct->pivot->stock - $data['stock'];
            $this->warehouseRepository->updateProductStock(
                $warehouse,
                $data['product_id'],
                $newWarehouseStock
            );

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Return product dari merchant ke warehouse
     * Stock di merchant berkurang, stock di warehouse bertambah
     */
    public function returnProductToWarehouse(array $data): Merchant
    {
        return DB::transaction(function () use ($data) {
            $merchant = $this->merchantRepository->getMerchantById($data['merchant_id'], ['*']);

            $merchantProduct = $merchant->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if (!$merchantProduct) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di merchant ini']
                ]);
            }

            if ($merchantProduct->pivot->stock < $data['stock']) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok di merchant tidak mencukupi. Tersedia: ' . $merchantProduct->pivot->stock]
                ]);
            }

            $warehouse = $this->warehouseRepository->getWarehouseById($data['warehouse_id'], ['*']);

            $newMerchantStock = $merchantProduct->pivot->stock - $data['stock'];

            if ($newMerchantStock <= 0) {
                $this->merchantRepository->detachProducts($merchant, [$data['product_id']]);
            } else {
                $this->merchantRepository->updateProductStock(
                    $merchant,
                    $data['product_id'],
                    $newMerchantStock
                );
            }

            $warehouseProduct = $warehouse->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if ($warehouseProduct) {
                $newWarehouseStock = $warehouseProduct->pivot->stock + $data['stock'];
                $this->warehouseRepository->updateProductStock(
                    $warehouse,
                    $data['product_id'],
                    $newWarehouseStock
                );
            } else {
                $this->warehouseRepository->attachProducts($warehouse, [
                    $data['product_id'] => ['stock' => $data['stock']]
                ]);
            }

            return $merchant->fresh(['keeper', 'products.category']);
        });
    }

    /**
     * Transfer product antar merchant
     */
    public function transferProductBetweenMerchants(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $sourceMerchant = $this->merchantRepository->getMerchantById($data['source_merchant_id'], ['*']);

            $sourceProduct = $sourceMerchant->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if (!$sourceProduct) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak ditemukan di merchant asal']
                ]);
            }

            if ($sourceProduct->pivot->stock < $data['stock']) {
                throw ValidationException::withMessages([
                    'stock' => ['Stok di merchant asal tidak mencukupi. Tersedia: ' . $sourceProduct->pivot->stock]
                ]);
            }

            $targetMerchant = $this->merchantRepository->getMerchantById($data['target_merchant_id'], ['*']);

            $newSourceStock = $sourceProduct->pivot->stock - $data['stock'];

            if ($newSourceStock <= 0) {
                $this->merchantRepository->detachProducts($sourceMerchant, [$data['product_id']]);
            } else {
                $this->merchantRepository->updateProductStock(
                    $sourceMerchant,
                    $data['product_id'],
                    $newSourceStock
                );
            }

            $targetProduct = $targetMerchant->products()
                ->where('product_id', $data['product_id'])
                ->first();

            if ($targetProduct) {
                $newTargetStock = $targetProduct->pivot->stock + $data['stock'];
                $this->merchantRepository->updateProductStock(
                    $targetMerchant,
                    $data['product_id'],
                    $newTargetStock
                );
            } else {
                $this->merchantRepository->attachProducts($targetMerchant, [
                    $data['product_id'] => ['stock' => $data['stock']]
                ]);
            }

            return [
                'source_merchant' => $sourceMerchant->fresh(['keeper', 'products.category']),
                'target_merchant' => $targetMerchant->fresh(['keeper', 'products.category'])
            ];
        });
    }

    /**
     * Get stock movement history between warehouse and merchant
     */
    public function getStockMovementHistory(string $merchantId, string $productId): array
    {
        $merchant = $this->merchantRepository->getMerchantById($merchantId, ['id', 'name']);

        $merchantProduct = $merchant->products()
            ->where('product_id', $productId)
            ->first();

        if (!$merchantProduct) {
            throw ValidationException::withMessages([
                'product_id' => ['Produk tidak ditemukan di merchant ini']
            ]);
        }

        return [
            'merchant' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
            ],
            'product' => [
                'id' => $merchantProduct->id,
                'name' => $merchantProduct->name,
                'current_stock' => $merchantProduct->pivot->stock,
            ],
            // You can extend this to include actual transaction logs
            'movements' => []
        ];
    }
}
