<?php

namespace App\Repositories;

use App\Models\Merchant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MerchantRepository
{

    /**
     * Get all merchants with pagination
     */
    public function getAllMerchant(array $field = ['*']): LengthAwarePaginator
    {
        return Merchant::select($field)
            ->with(['keeper', 'products.category'])
            ->latest()
            ->paginate(25);
    }
    /**
     * Get merchant by slug
     */
    public function getMerchantBySlug(string $slug, array $field = ['*']): Merchant
    {
        return Merchant::select($field)
            ->with(['keeper', 'products.category'])
            ->where('slug', $slug)
            ->firstOrFail();
    }
    /**
     * Get merchant by ID
     */
    public function getMerchantById(string $id, array $field = ['*']): Merchant
    {
        return Merchant::select($field)
            ->with(['keeper', 'products.category'])
            ->findOrFail($id);
    }
    /**
     * Get merchant by keeper ID
     */
    public function getMerchantByKeeperId(string $keeperId, array $field = ['*']): Merchant
    {
        return Merchant::select($field)
            ->with(['products.category'])
            ->where('keeper_id', $keeperId)
            ->firstOrFail();
    }

    /**
     * Create new merchant
     */
    public function createMerchant(array $data): Merchant
    {
        return Merchant::create($data);
    }

    /**
     * Update merchant
     */
    public function updateMerchant(Merchant $merchant, array $data): Merchant
    {
        $merchant->update($data);
        return $merchant->fresh(['keeper', 'products.category']);
    }
    /**
     * Delete merchant (soft delete)
     */
    public function deleteMerchant(Merchant $merchant): bool
    {
        return $merchant->delete();
    }

    /**
     * Attach products to merchant with stock
     */
    public function attachProducts(Merchant $merchant, array $products): void
    {
        // Format: ['product_id' => ['stock' => 100]]
        $merchant->products()->attach($products);
    }

    /**
     * Sync products to merchant with stock
     */
    public function syncProducts(Merchant $merchant, array $products): void
    {
        // Format: ['product_id' => ['stock' => 100]]
        $merchant->products()->sync($products);
    }

    /**
     * Update product stock in merchant
     */
    public function updateProductStock(Merchant $merchant, string $productId, int $stock): void
    {
        $merchant->products()->updateExistingPivot($productId, ['stock' => $stock]);
    }

    /**
     * Detach products from merchant
     */
    public function detachProducts(Merchant $merchant, array $productIds = []): void
    {
        if (empty($productIds)) {
            $merchant->products()->detach();
        } else {
            $merchant->products()->detach($productIds);
        }
    }
}
