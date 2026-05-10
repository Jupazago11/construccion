<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        /** @var Provider $provider */
        $provider = $this->route('provider');
        $companyId = $user->isSuperAdmin()
            ? $this->input('company_id', $provider?->company_id)
            : $user->company_id;

        return [
            'company_id' => [
                Rule::requiredIf($user->isSuperAdmin()),
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('providers', 'name')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($provider?->id),
            ],
            'document_number' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
