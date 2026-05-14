<?php

namespace App\Http\Requests;

use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSubgroupUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subgroup = $this->route('productSubgroup');

        return [
            'product_group_id' => [
                'required',
                'integer',
                Rule::exists(ProductGroup::class, 'id')->where('company_id', $subgroup?->company_id)->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ProductSubgroup::class, 'name')
                    ->where('product_group_id', $this->input('product_group_id'))
                    ->ignore($subgroup?->id),
            ],
        ];
    }
}
