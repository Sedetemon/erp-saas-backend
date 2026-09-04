<?php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPosOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_product_id' => ['required', 'uuid', 'exists:pos_products,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
