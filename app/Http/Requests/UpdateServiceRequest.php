<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optional image validation
        ];
    }
}
