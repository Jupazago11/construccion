<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

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
                Rule::unique('subcategories', 'name')->where(
                    fn ($query) => $query->where('category_id', $this->input('category_id'))
                ),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
