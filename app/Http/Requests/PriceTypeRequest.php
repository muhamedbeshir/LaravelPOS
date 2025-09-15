<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PriceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We'll handle authorization through policies or middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $priceTypeId = $this->route('priceType.id') ?? $this->route('priceType');

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('price_types', 'code')->ignore($priceTypeId),
            ],
            'sort_order' => 'required|integer|min:1',
            'is_default' => 'nullable',
            'is_active' => 'nullable',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set correct boolean values for checkboxes
        $this->merge([
            'is_default' => $this->has('is_default'),
            'is_active' => $this->has('is_active')
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'code' => 'الكود',
            'sort_order' => 'ترتيب العرض',
            'is_default' => 'افتراضي',
            'is_active' => 'نشط',
        ];
    }
}
