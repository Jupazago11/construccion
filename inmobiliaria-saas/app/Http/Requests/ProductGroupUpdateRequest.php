<?php

namespace App\Http\Requests;

use App\Models\ProductGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductGroupUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $group = $this->route('productGroup');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ProductGroup::class, 'name')
                    ->where('company_id', $group?->company_id)
                    ->ignore($group?->id),
            ],
        ];
    }
}
