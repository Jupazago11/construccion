<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetTypeStoreRequest extends FormRequest
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
                Rule::unique(AssetType::class, 'name')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', '!=', EntityStatus::Deleted->value))
                    ->ignore($this->route('assetType')),
            ],
            'status' => ['required', Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value])],
        ];
    }
}
