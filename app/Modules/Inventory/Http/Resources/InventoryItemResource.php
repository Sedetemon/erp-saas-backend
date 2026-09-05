<?php

namespace App\Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'linked_to' => $this->itemable_type ? class_basename($this->itemable_type) : null,
            'stock' => $this->whenLoaded('stock', fn () => $this->stock ? [
                'quantity' => (float) $this->stock->quantity,
                'alert_threshold' => (float) $this->stock->alert_threshold,
                'is_low' => $this->stock->isLow(),
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
