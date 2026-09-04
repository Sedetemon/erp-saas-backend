<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'amount_paid' => $this->amountPaid(),
            'balance_due' => $this->balanceDue(),
            'issued_at' => $this->issued_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'guest' => new GuestResource($this->whenLoaded('guest')),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
