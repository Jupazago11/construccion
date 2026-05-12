<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\AssetType;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'purchase_value' => $this->normalizeIntegerAmount($this->input('purchase_value')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'company_id' => [
                Rule::requiredIf($user->isSuperAdmin()),
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'asset_type_id' => [
                'required',
                'integer',
                Rule::exists(AssetType::class, 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $user->isSuperAdmin() ? $this->integer('company_id') : $user->company_id)
                        ->where('status', EntityStatus::Active->value)),
            ],
            'asset_condition' => ['required', Rule::in(['new', 'used'])],
            'purchase_value' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ];
    }

    protected function normalizeIntegerAmount(mixed $value): string|null
    {
        $normalized = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
