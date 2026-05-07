<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var Subcategory $subcategory */
        $subcategory = $this->route('subcategory');

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where(
                    fn ($query) => $query->where('project_id', $project?->id)
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')
                    ->where(fn ($query) => $query->where('category_id', $this->input('category_id')))
                    ->ignore($subcategory?->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
