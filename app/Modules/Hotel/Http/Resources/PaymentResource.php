<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'paid_at' => $this->paid_at?->toDateTimeString(),
        ];
    }
}
