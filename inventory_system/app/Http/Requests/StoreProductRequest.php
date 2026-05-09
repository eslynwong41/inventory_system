<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policy
    }
 
    public function rules(): array
    {
        return [
            'category_id'         => ['required', 'integer', 'exists:categories,id'],
            'name'                => ['required', 'string', 'max:200'],
            'sku'                 => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'description'         => ['nullable', 'string', 'max:5000'],
            'price'               => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'cost_price'          => ['nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'stock_quantity'      => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'unit'                => ['nullable', 'string', 'max:50'],
            'barcode'             => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'image_url'           => ['nullable', 'url', 'max:500'],
            'is_active'           => ['nullable', 'boolean'],
            'supplier_ids'        => ['nullable', 'array'],
            'supplier_ids.*'      => ['integer', 'exists:suppliers,id'],
            'supplier_pivot'      => ['nullable', 'array'],
            'supplier_pivot.*.supplier_id'    => ['required_with:supplier_pivot', 'exists:suppliers,id'],
            'supplier_pivot.*.supply_price'   => ['nullable', 'numeric', 'min:0'],
            'supplier_pivot.*.lead_time_days' => ['nullable', 'integer', 'min:0'],
            'supplier_pivot.*.is_preferred'   => ['nullable', 'boolean'],
        ];
    }
 
    public function messages(): array
    {
        return [
            'category_id.required' => 'A category must be selected.',
            'category_id.exists'   => 'The selected category does not exist.',
            'price.required'       => 'Product price is required.',
            'price.min'            => 'Price cannot be negative.',
            'sku.unique'           => 'This SKU is already in use.',
            'barcode.unique'       => 'This barcode is already in use.',
        ];
    }
}
