<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Enums\SystemRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $authUser = $this->user();
        /** @var User $managedUser */
        $managedUser = $this->route('user');
        $availableRoles = $authUser->isSuperAdmin()
            ? [SystemRole::CompanyAdmin->value, SystemRole::Operator->value, SystemRole::Viewer->value]
            : [SystemRole::Operator->value, SystemRole::Viewer->value];

        if ($managedUser->hasRole(SystemRole::CompanyAdmin->value)) {
            $availableRoles[] = SystemRole::CompanyAdmin->value;
        }

        return [
            'company_id' => [
                Rule::requiredIf($authUser->isSuperAdmin() && ! $managedUser->isSuperAdmin()),
                'nullable',
                'integer',
                Rule::exists(Company::class, 'id')->where(fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value)),
            ],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique('users', 'username')->ignore($managedUser?->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managedUser?->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'status' => [
                Rule::requiredIf($this->canManageStatus($authUser, $managedUser)),
                'nullable',
                Rule::in([EntityStatus::Active->value, EntityStatus::Inactive->value]),
            ],
            'role' => ['required', Rule::in($availableRoles)],
        ];
    }

    protected function canManageStatus(User $authUser, User $managedUser): bool
    {
        if ($authUser->isSuperAdmin()) {
            return true;
        }

        if (! $authUser->hasRole(SystemRole::CompanyAdmin->value) || $authUser->is($managedUser)) {
            return false;
        }

        return $managedUser->hasAnyRole([SystemRole::Operator->value, SystemRole::Viewer->value]);
    }
}
