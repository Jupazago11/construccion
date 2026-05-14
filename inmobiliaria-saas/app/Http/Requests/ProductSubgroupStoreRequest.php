<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSubgroupStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->resolvedCompanyId();

        return [
            'company_id' => [
                $this->user()->isSuperAdmin() ? 'required' : 'nullable',
                'integer',
                Rule::exists(Company::class, 'id'),
            ],
            'product_group_id' => [
                'required',
                'integer',
                Rule::exists(ProductGroup::class, 'id')->where('company_id', $companyId)->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ProductSubgroup::class, 'name')->where('product_group_id', $this->input('product_group_id')),
            ],
        ];
    }

    public function resolvedCompanyId(): ?int
    {
        return $this->user()->isSuperAdmin()
            ? (int) $this->input('company_id')
            : $this->user()->company_id;
    }
}
