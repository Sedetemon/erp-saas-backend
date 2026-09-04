<?php

namespace App\Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomType = $this->route('room_type');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('room_types', 'code')->ignore($roomType?->id),
            ],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'capacity_adults' => ['required', 'integer', 'min:1'],
            'capacity_children' => ['nullable', 'integer', 'min:0'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
            'is_active' => ['boolean'],
        ];
    }
}
