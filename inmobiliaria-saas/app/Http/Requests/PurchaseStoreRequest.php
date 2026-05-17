<?php

namespace App\Http\Requests;

use App\Models\CatalogActivity;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\Provider2;
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
                Rule::exists(Provider2::class, 'id')->where('company_id', $this->resolvedCompanyId())->where('status', 'active'),
            ],
            'invoice_id' => [
                'nullable',
                'integer',
                Rule::exists(Invoice::class, 'id')
                    ->where('project_id', $this->input('project_id'))
                    ->where('type', 'purchase')
                    ->where('status', 'open'),
            ],
            'is_activity' => ['nullable', 'boolean'],
            'product_id' => [
                Rule::requiredIf(! $this->boolean('is_activity')),
                'nullable',
                'integer',
                Rule::exists(Product::class, 'id')->where('company_id', $this->resolvedCompanyId())->where('status', 'active'),
            ],
            'activity_id' => [
                Rule::requiredIf($this->boolean('is_activity')),
                'nullable',
                'integer',
                Rule::exists(CatalogActivity::class, 'id')->where('company_id', $this->resolvedCompanyId())->where('status', 'active'),
            ],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'El proyecto es obligatorio.',
            'project_id.exists' => 'El proyecto seleccionado no existe o no tienes acceso.',
            'purchase_date.required' => 'La fecha de la compra es obligatoria.',
            'provider_id.required' => 'El proveedor es obligatorio.',
            'provider_id.exists' => 'El proveedor seleccionado no existe o no está activo.',
            'invoice_id.exists' => 'La factura asociada no está disponible para agregar items (debe estar abierta).',
            'product_id.required' => 'El producto es obligatorio cuando el movimiento no es una actividad.',
            'product_id.exists' => 'El producto seleccionado no existe o no está activo.',
            'activity_id.required' => 'La actividad es obligatoria cuando marcas el movimiento como actividad.',
            'activity_id.exists' => 'La actividad seleccionada no existe o no está activa.',
            'unit_price.required' => 'El valor unitario es obligatorio.',
            'unit_price.numeric' => 'El valor unitario debe ser un número.',
            'unit_price.min' => 'El valor unitario no puede ser negativo.',
            'quantity.numeric' => 'La cantidad debe ser un número.',
            'quantity.min' => 'La cantidad no puede ser negativa.',
        ];
    }

    protected function resolvedCompanyId(): ?int
    {
        return Project::query()->whereKey($this->input('project_id'))->value('company_id');
    }
}
