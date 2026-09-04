<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reservation_id' => $this->reservation_id,
            'room_id'        => $this->room_id,
            'room_type_id'   => $this->room_type_id,
            'rate_per_night' => (float) $this->rate_per_night,
            'nights'         => (int) $this->nights,
            'subtotal'       => (float) $this->subtotal,
            'room'           => new RoomResource($this->whenLoaded('room')),
            'room_type'      => new RoomTypeResource($this->whenLoaded('roomType')),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
