<?php

namespace App\Http\Requests;

use App\Models\CatalogActivityGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogActivityGroupUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('activityGroup');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CatalogActivityGroup::class, 'name')
                    ->where('company_id', $group?->company_id)
                    ->ignore($group?->id),
            ],
        ];
    }
}
