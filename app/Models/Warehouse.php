<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes, UUID, HasSlug;

    protected $fillable = ['slug', 'name', 'phone', 'alamat', 'thumbnail', 'description'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'warehouse_product')
            ->withPivot('stock')
            ->withTimestamps();
    }
    public function getThumbnailAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Storage::url($value);
    }
}
