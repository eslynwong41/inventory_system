<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
 
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
 
        return [
            'category_id'         => ['sometimes', 'integer', 'exists:categories,id'],
            'name'                => ['sometimes', 'string', 'max:200'],
            'sku'                 => ['sometimes', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'description'         => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price'               => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'cost_price'          => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'stock_quantity'      => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'unit'                => ['sometimes', 'string', 'max:50'],
            'barcode'             => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($productId)],
            'image_url'           => ['sometimes', 'nullable', 'url', 'max:500'],
            'is_active'           => ['sometimes', 'boolean'],
            'supplier_ids'        => ['sometimes', 'nullable', 'array'],
            'supplier_ids.*'      => ['integer', 'exists:suppliers,id'],
            'supplier_pivot'      => ['sometimes', 'nullable', 'array'],
            'supplier_pivot.*.supplier_id'    => ['required_with:supplier_pivot', 'exists:suppliers,id'],
            'supplier_pivot.*.supply_price'   => ['nullable', 'numeric', 'min:0'],
            'supplier_pivot.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'supplier_pivot.*.is_preferred'   => ['nullable', 'boolean'],
        ];
    }
}
