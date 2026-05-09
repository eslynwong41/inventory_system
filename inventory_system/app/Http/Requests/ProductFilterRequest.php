<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'category_ids'    => ['nullable', 'array'],
            'category_ids.*'  => ['integer', 'exists:categories,id'],
            'min_price'       => ['nullable', 'numeric', 'min:0'],
            'max_price'       => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'stock_status'    => ['nullable', 'string', 'in:in_stock,low_stock,out_of_stock'],
            'is_active'       => ['nullable', 'boolean'],
            'search'          => ['nullable', 'string', 'max:100'],
            'sort_by'         => ['nullable', 'string', 'in:name,price,stock_quantity,created_at'],
            'sort_direction'  => ['nullable', 'string', 'in:asc,desc'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'supplier_id'     => ['nullable', 'integer', 'exists:suppliers,id'],
        ];
    }
}
