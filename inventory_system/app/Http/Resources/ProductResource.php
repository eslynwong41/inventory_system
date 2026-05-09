<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'sku'                 => $this->sku,
            'description'         => $this->description,
            'price'               => (float) $this->price,
            'cost_price'          => $this->cost_price ? (float) $this->cost_price : null,
            'profit_margin'       => $this->profit_margin,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'stock_status'        => $this->stock_status,
            'unit'                => $this->unit,
            'barcode'             => $this->barcode,
            'image_url'           => $this->image_url,
            'is_active'           => $this->is_active,
            'category'            => new CategoryResource($this->whenLoaded('category')),
            'suppliers'           => SupplierResource::collection($this->whenLoaded('suppliers')),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
            'deleted_at'          => $this->when(
                $this->trashed(),
                $this->deleted_at?->toISOString()
            ),
        ];
    }
    }
}
