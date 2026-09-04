<?php

namespace App\Modules\Hotel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'notes' => $this->notes,
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'room' => new RoomResource($this->whenLoaded('room')),
            'assigned_to' => $this->whenLoaded('assignedTo', fn () => [
                'id' => $this->assignedTo->id,
                'name' => $this->assignedTo->name,
            ]),
        ];
    }
}
