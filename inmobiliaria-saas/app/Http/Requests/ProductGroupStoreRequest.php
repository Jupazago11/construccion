<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ProductGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductGroupStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => [
                $this->user()->isSuperAdmin() ? 'required' : 'nullable',
                'integer',
                Rule::exists(Company::class, 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ProductGroup::class, 'name')->where('company_id', $this->resolvedCompanyId()),
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
