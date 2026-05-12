<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\AssetNoveltyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetNoveltyTypeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->isSuperAdmin()
            ? $this->integer('company_id')
            : $this->user()->company_id;

        return [
            'company_id' => ['nullable', 'integer'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(AssetNoveltyType::class, 'name')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', '!=', EntityStatus::Deleted->value))
                    ->ignore($this->route('assetNoveltyType')),
            ],
            'adds_value' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value])],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['adds_value'] = $this->boolean('adds_value');

        return $data;
    }
}
