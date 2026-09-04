<?php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['free', 'occupied', 'reserved'])],
        ];
    }
}
