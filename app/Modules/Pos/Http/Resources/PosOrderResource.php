<?php

namespace App\Modules\Pos\Http\Resources;

use App\Modules\Hotel\Http\Resources\GuestResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'type' => $this->type,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'closed_at' => $this->closed_at?->toDateTimeString(),
            'table' => new PosTableResource($this->whenLoaded('table')),
            'guest' => new GuestResource($this->whenLoaded('guest')),
            'items' => PosOrderItemResource::collection($this->whenLoaded('items')),
            'invoice_id' => $this->invoice_id,
        ];
    }
}
