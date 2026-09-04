<?php

namespace App\Modules\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => (float) $this->price,
            'is_active' => $this->is_active,
            'category' => new PosCategoryResource($this->whenLoaded('category')),
        ];
    }
}
