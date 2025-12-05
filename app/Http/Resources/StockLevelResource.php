<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,
            'total_stock' => $this->total_stock,
            // Pastikan method ini ada di Model Product
            'warehouse_stock' => $this->getWarehouseProductStock(),
            'merchant_stock' => $this->getMerchantProductStock(),
        ];
    }
}
