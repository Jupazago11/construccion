<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $companyId = $user->isSuperAdmin()
            ? $this->input('company_id')
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
                Rule::unique('projects', 'name')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'project_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'country' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_reference' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['planning', 'active', 'paused', 'completed', 'cancelled', 'deleted'])],
        ];
    }
}
