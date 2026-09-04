<?php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_table_id' => ['nullable', 'uuid', 'exists:pos_tables,id'],
            'guest_id' => ['nullable', 'uuid', 'exists:guests,id'],
            'reservation_id' => ['nullable', 'uuid', 'exists:reservations,id'],
            'type' => ['nullable', Rule::in(['dine_in', 'room_service', 'takeaway'])],
        ];
    }
}
