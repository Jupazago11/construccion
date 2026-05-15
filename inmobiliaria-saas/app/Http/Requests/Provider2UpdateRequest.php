<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Provider2;
use App\Models\Provider2Type;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Provider2UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        /** @var Provider2 $provider2 */
        $provider2 = $this->route('provider2');
        $companyId = $user->isSuperAdmin()
            ? $this->input('company_id', $provider2?->company_id)
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
                Rule::unique('providers2', 'name')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($provider2?->id),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'provider2_type_id' => [
                'nullable',
                'integer',
                Rule::exists(Provider2Type::class, 'id')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('status', EntityStatus::Active->value)),
            ],
            'document_number' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
