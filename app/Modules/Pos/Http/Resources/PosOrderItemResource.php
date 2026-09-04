<?php

namespace App\Modules\Pos\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new PosProductResource($this->whenLoaded('product')),
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total' => (float) $this->total,
            'notes' => $this->notes,
        ];
    }
}
