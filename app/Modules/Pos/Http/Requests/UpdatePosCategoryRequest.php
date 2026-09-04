<?php
// app/Modules/Pos/Http/Requests/UpdatePosCategoryRequest.php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
