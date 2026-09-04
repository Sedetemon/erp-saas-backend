<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'reservation_number' => $this->reservation_number,
            'check_in_date'      => $this->check_in_date?->toDateString(),
            'check_out_date'     => $this->check_out_date?->toDateString(),
            'nights'             => $this->nights(),
            'adults'             => $this->adults,
            'children'           => $this->children,
            'status'             => $this->status,
            'source'             => $this->source,
            'notes'              => $this->notes,
            'total'              => $this->total(),
            'balance'            => $this->balance,
            'guest'              => new GuestResource($this->whenLoaded('guest')),
            'rooms'              => ReservationRoomResource::collection($this->whenLoaded('reservationRooms')),
            'invoices'           => InvoiceResource::collection($this->whenLoaded('invoices')),
            'ledgers'            => GuestLedgerResource::collection($this->whenLoaded('ledgers')),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}