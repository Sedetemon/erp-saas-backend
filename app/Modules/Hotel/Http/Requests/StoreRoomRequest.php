<?php

namespace App\Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $room = $this->route('room');

        return [
            'room_type_id' => ['required', 'uuid', 'exists:room_types,id'],
            'number' => [
                'required', 'string', 'max:20',
                Rule::unique('rooms', 'number')->ignore($room?->id),
            ],
            'floor' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['available', 'occupied', 'cleaning', 'maintenance'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
