<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price, // Dari Accessor Model
            'thumbnail' => $this->thumbnail,
            'is_popular' => (bool) $this->is_popular,
            'category' => new CategoryResource($this->whenLoaded('category')),
            // Menampilkan total stok global (Gudang + Semua Toko)
            'total_global_stock' => $this->total_stock,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
