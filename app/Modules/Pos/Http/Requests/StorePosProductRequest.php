<?php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_category_id' => ['required', 'uuid', 'exists:pos_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            // Pas de route parameter à cette étape (création) : rien à ignorer.
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('pos_products', 'sku')],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
