<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\AssetNoveltyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetNoveltyStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cost' => $this->normalizeIntegerAmount($this->input('cost')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_novelty_type_id' => [
                'required',
                'integer',
                Rule::exists(AssetNoveltyType::class, 'id')
                    ->where(fn ($query) => $query->where('status', EntityStatus::Active->value)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'asset_status' => ['nullable', 'string', 'max:255'],
            'novelty_date' => ['required', 'date'],
        ];
    }

    protected function normalizeIntegerAmount(mixed $value): string|null
    {
        $normalized = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
