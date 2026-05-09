<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'address'        => $this->address,
            'city'           => $this->city,
            'country'        => $this->country,
            'contact_person' => $this->contact_person,
            'full_address'   => $this->full_address,
            'is_active'      => $this->is_active,
            'pivot'          => $this->when($this->pivot !== null, [
                'supply_price'   => $this->pivot?->supply_price ? (float) $this->pivot->supply_price : null,
                'lead_time_days' => $this->pivot?->lead_time_days,
                'is_preferred'   => (bool) $this->pivot?->is_preferred,
            ]),
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
