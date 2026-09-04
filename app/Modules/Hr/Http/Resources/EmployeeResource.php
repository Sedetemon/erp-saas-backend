<?php

namespace App\Modules\Hr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'position' => $this->position,
            'department' => $this->department,
            'hire_date' => $this->hire_date?->toDateString(),
            'status' => $this->status,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'national_id' => $this->national_id,
            'address' => $this->address,
            'active_contract' => $this->activeContract()
                ? new EmployeeContractResource($this->activeContract())
                : null,
        ];
    }
}
