<?php

namespace App\Http\Requests;

use App\Models\CatalogActivity;
use App\Models\CatalogActivityGroup;
use App\Models\CatalogActivitySubgroup;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogActivityStoreRequest extends FormRequest
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
            'activity_group_id' => [
                'required',
                'integer',
                Rule::exists(CatalogActivityGroup::class, 'id')->where('company_id', $companyId)->where('status', 'active'),
            ],
            'activity_subgroup_id' => [
                'required',
                'integer',
                Rule::exists(CatalogActivitySubgroup::class, 'id')
                    ->where('company_id', $companyId)
                    ->where('activity_group_id', $this->input('activity_group_id'))
                    ->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CatalogActivity::class, 'name')->where('activity_subgroup_id', $this->input('activity_subgroup_id')),
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
