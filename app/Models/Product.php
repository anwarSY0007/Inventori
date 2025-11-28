<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{

    use HasFactory, SoftDeletes, UUID, HasSlug;

    protected $fillable = ['slug', 'name', 'description', 'price', 'thumbnail', 'category_id', 'is_popular'];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_product')
            ->withPivot('stock')
            ->withTimestamps();
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_product')
            ->withPivot('stock')
            ->withTimestamps();
    }
    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionProduct::class);
    }
    public function getWarehouseProductStock(): int
    {
        return $this->warehouses()->sum('stock');
    }
    public function getMerchantProductStock(): int
    {
        return $this->merchants()->sum('stock');
    }
    public function getThumbnailAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Storage::url($value);
    }

    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->getWarehouseProductStock() + $this->getMerchantProductStock();
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => 'Rp ' . number_format($this->price, 0, ',', '.')
        );
    }
}
