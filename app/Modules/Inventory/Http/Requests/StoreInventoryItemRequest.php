<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:inventory_items,sku'],
            'unit' => ['nullable', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            // Rattachement optionnel à un produit POS existant.
            'pos_product_id' => ['nullable', 'uuid', 'exists:pos_products,id'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'alert_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
