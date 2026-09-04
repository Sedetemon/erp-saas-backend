<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'base_price' => (float) $this->base_price,
            'capacity_adults' => $this->capacity_adults,
            'capacity_children' => $this->capacity_children,
            'amenities' => $this->amenities,
            'is_active' => $this->is_active,
            'rooms_count' => $this->whenCounted('rooms'),
        ];
    }
}
