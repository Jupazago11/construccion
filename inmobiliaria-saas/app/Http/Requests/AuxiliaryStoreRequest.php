<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Project;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuxiliaryStoreRequest extends FormRequest
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
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists(Subcategory::class, 'id')->where(function ($query) use ($project) {
                    $query->whereIn(
                        'category_id',
                        Category::query()
                            ->where('project_id', $project?->id)
                            ->select('id')
                    );
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('auxiliaries', 'name')->where(
                    fn ($query) => $query->where('subcategory_id', $this->input('subcategory_id'))
                ),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
