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

        if (strlen($slug) > 100) {
            $slug = substr($slug, 0, 100);
        }

        $originalSlug = $slug;
        $count = 1;

        while (static::whereRaw('LOWER(slug) = ?', [strtolower($slug)])->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;

            if ($count > 1000) {
                $slug = "{$originalSlug}-" . Str::random(6);
                break;
            }
        }

        return $slug;
    }
}
