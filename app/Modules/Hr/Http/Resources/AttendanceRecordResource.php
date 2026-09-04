<?php

namespace App\Modules\Hr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'date' => $this->date?->toDateString(),
            'clock_in' => $this->clock_in?->toDateTimeString(),
            'clock_out' => $this->clock_out?->toDateTimeString(),
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
