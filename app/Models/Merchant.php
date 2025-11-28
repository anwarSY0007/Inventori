<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Merchant extends Model
{
    use HasFactory, SoftDeletes, UUID, HasSlug;

    protected $fillable = ['slug', 'name', 'phone', 'alamat', 'thumbnail', 'description', 'keeper_id'];

    public function keeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keeper_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'merchant_product')
            ->using(MerchantProduct::class)
            ->withPivot('stock')
            ->withTimestamps();
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    public function getThumbnailAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Storage::url($value);
    }
}
