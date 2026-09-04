<?php

namespace App\Modules\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pos_category_id' => 'sometimes|exists:pos_categories,id',
            'name'            => 'sometimes|string|max:255',
            // Format uniquement ici : l'unicité (hors le produit courant)
            // est vérifiée explicitement dans PosProductController::update(),
            // où $posProduct est garanti résolu sans ambiguïté de timing
            // avec la substitution de route.
            'sku'             => 'nullable|string|max:50',
            'price'           => 'sometimes|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
            'stock'           => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ];
    }
}
