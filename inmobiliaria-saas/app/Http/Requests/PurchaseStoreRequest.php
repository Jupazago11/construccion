<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Project;
use App\Models\Provider;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseStoreRequest extends FormRequest
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
            'purchase_date' => ['required', 'date'],
            'provider_id' => [
                'required',
                'integer',
                Rule::exists(Provider::class, 'id')->where('company_id', $this->resolvedCompanyId())->where('status', 'active'),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists(Invoice::class, 'id')
                    ->where('project_id', $this->input('project_id'))
                    ->where('provider_id', $this->input('provider_id'))
                    ->where('type', 'purchase')
                    ->where('status', 'open'),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('company_id', $this->resolvedCompanyId())->where('status', 'active'),
            ],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function resolvedCompanyId(): ?int
    {
        return Project::query()->whereKey($this->input('project_id'))->value('company_id');
    }
}
