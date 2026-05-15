<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Asset2Type;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Asset2TypeStoreRequest extends FormRequest
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
                Rule::unique(Asset2Type::class, 'name')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('status', '!=', EntityStatus::Deleted->value))
                    ->ignore($this->route('asset2Type')),
            ],
            'status' => ['required', Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value])],
        ];
    }
}
