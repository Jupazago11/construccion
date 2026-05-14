<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Invoice;
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
            'description' => ['nullable', 'string'],
            'provider_id' => [
                'required',
                'integer',
                Rule::exists(Provider::class, 'id')->where(function ($query) {
                    $query
                        ->where('company_id', $this->resolvedCompanyId())
                        ->where('status', 'active');
                }),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists(Invoice::class, 'id')
                    ->where('project_id', $this->input('project_id'))
                    ->where('provider_id', $this->input('provider_id'))
                    ->where('type', 'expense')
                    ->where('status', 'open'),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where(function ($query) {
                    $query
                        ->where('company_id', $this->resolvedCompanyId())
                        ->where('status', 'active');
                }),
            ],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'string', 'max:255'],
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
