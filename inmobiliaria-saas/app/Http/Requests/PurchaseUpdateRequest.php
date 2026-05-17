<?php

namespace App\Http\Requests;

use App\Models\CatalogActivity;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Provider2;
use Illuminate\Validation\Rule;

class PurchaseUpdateRequest extends PurchaseStoreRequest
{
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
                Rule::exists(Provider2::class, 'id')
                    ->where('company_id', $this->resolvedCompanyId())
                    ->where('status', 'active'),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists(Invoice::class, 'id')
                    ->where('project_id', $this->input('project_id'))
                    ->where('type', 'purchase'),
            ],
            'is_activity' => ['nullable', 'boolean'],
            'product_id' => [
                Rule::requiredIf(! $this->boolean('is_activity')),
                'nullable',
                'integer',
                Rule::exists(Product::class, 'id')
                    ->where('company_id', $this->resolvedCompanyId())
                    ->where('status', 'active'),
            ],
            'activity_id' => [
                Rule::requiredIf($this->boolean('is_activity')),
                'nullable',
                'integer',
                Rule::exists(CatalogActivity::class, 'id')
                    ->where('company_id', $this->resolvedCompanyId())
                    ->where('status', 'active'),
            ],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
