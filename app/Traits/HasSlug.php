<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Boot the trait.
     * Naming convention: boot[TraitName]
     */
    protected static function bootHasSlug()
    {
        static::creating(function ($model) {
            // Cek apakah model punya method 'getSlugSourceColumn'
            $source = $model->getSlugSourceColumn();
            
            // Generate slug jika belum diisi manual
            if (empty($model->slug) && $model->{$source}) {
                $model->slug = $model->generateUniqueSlug($model->{$source});
            }
        });
    }

    /**
     * Define source column. Default: 'name'.
     * Override this in your model if needed.
     */
    public function getSlugSourceColumn()
    {
        return 'name';
    }

    /**
     * Generate unique slug (handling duplicates).
     */
    protected function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        // Cek database apakah slug sudah ada
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}