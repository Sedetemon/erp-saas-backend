<?php

namespace App\Modules\Hotel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddReservationPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'mobile_money', 'bank_transfer'])],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
