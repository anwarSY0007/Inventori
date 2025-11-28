<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory, SoftDeletes, UUID, HasSlug;

    protected $fillable = ['slug', 'name', 'thumbnail', 'tagline'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function getThumbnailAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }
        return Storage::url($value);
    }
}
