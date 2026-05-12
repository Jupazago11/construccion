<?php

namespace App\Http\Requests;

use App\Models\Auxiliary;
use App\Models\Category;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'project_id' => [
                'required',
                'integer',
                Rule::exists(Project::class, 'id')->where(function ($query) use ($user) {
                    if (! $user->isSuperAdmin()) {
                        $query->where('company_id', $user->company_id);
                    }
                }),
            ],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'credit_card', 'debit_card', 'other'])],
            'description' => ['nullable', 'string'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where(
                    fn ($query) => $query
                        ->where('project_id', $this->input('project_id'))
                        ->where('status', 'active')
                ),
            ],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists(Subcategory::class, 'id')->where(
                    fn ($query) => $query
                        ->where('category_id', $this->input('category_id'))
                        ->where('status', 'active')
                ),
            ],
            'auxiliary_id' => [
                'nullable',
                'integer',
                Rule::exists(Auxiliary::class, 'id')->where(
                    fn ($query) => $query
                        ->where('subcategory_id', $this->input('subcategory_id'))
                        ->where('status', 'active')
                ),
            ],
            'provider_id' => [
                'nullable',
                'integer',
                Rule::exists(Provider::class, 'id')->where(function ($query) {
                    $query
                        ->where('company_id', $this->resolvedCompanyId())
                        ->where('status', 'active');
                }),
            ],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function resolvedCompanyId(): ?int
    {
        $projectId = $this->input('project_id');

        if (! $projectId) {
            return null;
        }

        return Project::query()->whereKey($projectId)->value('company_id');
    }
}
