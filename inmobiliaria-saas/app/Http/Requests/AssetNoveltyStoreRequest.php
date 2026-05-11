<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'cost' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:1000'],
            'asset_status' => ['required', 'string', 'max:255'],
            'novelty_date' => ['required', 'date'],
        ];
    }

    protected function normalizeIntegerAmount(mixed $value): string|null
    {
        $normalized = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
