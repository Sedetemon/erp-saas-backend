<?php
// app/Modules/Pos/Http/Requests/UpdatePosTableRequest.php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:free,occupied,reserved',
            'capacity' => 'sometimes|integer|min:1|max:20',
            'is_active' => 'nullable|boolean',
        ];
    }
}
