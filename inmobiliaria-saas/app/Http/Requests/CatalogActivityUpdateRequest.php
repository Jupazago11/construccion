<?php

namespace App\Http\Requests;

use App\Models\CatalogActivity;
use App\Models\CatalogActivityGroup;
use App\Models\CatalogActivitySubgroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogActivityUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $activity = $this->route('activity');

        return [
            'activity_group_id' => [
                'required',
                'integer',
                Rule::exists(CatalogActivityGroup::class, 'id')->where('company_id', $activity?->company_id)->where('status', 'active'),
            ],
            'activity_subgroup_id' => [
                'required',
                'integer',
                Rule::exists(CatalogActivitySubgroup::class, 'id')
                    ->where('company_id', $activity?->company_id)
                    ->where('activity_group_id', $this->input('activity_group_id'))
                    ->where('status', 'active'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CatalogActivity::class, 'name')
                    ->where('activity_subgroup_id', $this->input('activity_subgroup_id'))
                    ->ignore($activity?->id),
            ],
        ];
    }
}
