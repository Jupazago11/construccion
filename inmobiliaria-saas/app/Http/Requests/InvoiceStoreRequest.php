<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'type' => ['required', Rule::in(['expense', 'purchase'])],
            'project_id' => [
                'required',
                'integer',
                Rule::exists(Project::class, 'id')->where(function ($query) use ($user) {
                    if (! $user->isSuperAdmin()) {
                        $query->where('company_id', $user->company_id);
                    }
                }),
            ],
            'provider_id' => [
                'required',
                'integer',
                Rule::exists(Provider::class, 'id')
                    ->where('company_id', $this->resolvedCompanyId())
                    ->where('status', 'active'),
            ],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function resolvedCompanyId(): ?int
    {
        return Project::query()->whereKey($this->input('project_id'))->value('company_id');
    }
}
