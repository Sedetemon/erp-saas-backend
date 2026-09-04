<?php

namespace App\Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
