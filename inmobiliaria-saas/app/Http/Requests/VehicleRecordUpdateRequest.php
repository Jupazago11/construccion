<?php

namespace App\Http\Requests;

use App\Models\VehicleRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRecordUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->normalizeIntegerAmount($this->input('amount')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->string('category')->toString();
        $allowedConcepts = VehicleRecord::CONCEPTS_BY_CATEGORY[$category] ?? [];

        return [
            'category' => ['required', Rule::in(array_keys(VehicleRecord::CONCEPTS_BY_CATEGORY))],
            'concept' => ['required', Rule::in($allowedConcepts)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function normalizeIntegerAmount(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
