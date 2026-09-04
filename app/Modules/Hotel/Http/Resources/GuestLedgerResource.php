<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,           // charge | payment | discount
            'source' => $this->source,       // room_night | pos_order | payment ...
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
