<?php

namespace App\Http\Requests;

use App\Models\CatalogActivityGroup;
use App\Models\CatalogActivitySubgroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogActivitySubgroupUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subgroup = $this->route('activitySubgroup');

        return [
            'activity_group_id' => [
                'required',
                'integer',
                Rule::exists(CatalogActivityGroup::class, 'id')->where('company_id', $subgroup?->company_id)->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CatalogActivitySubgroup::class, 'name')
                    ->where('activity_group_id', $this->input('activity_group_id'))
                    ->ignore($subgroup?->id),
            ],
        ];
    }
}
