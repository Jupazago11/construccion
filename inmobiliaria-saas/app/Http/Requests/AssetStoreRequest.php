<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetStoreRequest extends FormRequest
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
            'asset_type' => ['required', Rule::in(['tool', 'equipment'])],
            'asset_condition' => ['required', Rule::in(['new', 'used'])],
            'purchase_value' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
        ];
    }

    protected function normalizeIntegerAmount(mixed $value): string|null
    {
        $normalized = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
