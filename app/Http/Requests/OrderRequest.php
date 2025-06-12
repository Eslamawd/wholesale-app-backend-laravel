<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'payment_status' => 'required|string|in:pending,completed,cancelled',
            'payment_method' => 'nullable|string|in:credit_card,paypal,cash',
        ];
    }
}
