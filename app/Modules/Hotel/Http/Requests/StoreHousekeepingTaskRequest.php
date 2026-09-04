<?php

namespace App\Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHousekeepingTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
            'type' => ['required', Rule::in(['checkout_cleaning', 'daily_cleaning', 'inspection', 'maintenance_report'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
