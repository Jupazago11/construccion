<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'product_group_id' => [
                'required',
                'integer',
                Rule::exists(ProductGroup::class, 'id')->where('company_id', $product?->company_id)->where('status', 'active'),
            ],
            'product_subgroup_id' => [
                'required',
                'integer',
                Rule::exists(ProductSubgroup::class, 'id')
                    ->where('company_id', $product?->company_id)
                    ->where('product_group_id', $this->input('product_group_id'))
                    ->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Product::class, 'name')
                    ->where('product_subgroup_id', $this->input('product_subgroup_id'))
                    ->ignore($product?->id),
            ],
        ];
    }
}
