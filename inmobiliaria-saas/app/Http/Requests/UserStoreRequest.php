<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $availableRoles = $user->isSuperAdmin()
            ? [SystemRole::CompanyAdmin->value, SystemRole::Operator->value, SystemRole::Viewer->value]
            : [SystemRole::Operator->value, SystemRole::Viewer->value];

        return [
            'company_id' => [
                Rule::requiredIf($user->isSuperAdmin()),
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)),
            ],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'status' => ['required', Rule::in(['active', 'inactive', 'deleted'])],
            'role' => ['required', Rule::in($availableRoles)],
        ];
    }
}
