<?php

namespace App\Http\Requests;

use App\Models\Auxiliary;
use App\Models\Project;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuxiliaryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var Auxiliary $auxiliary */
        $auxiliary = $this->route('auxiliary');

        return [
            'subcategory_id' => [
                'required',
                'integer',
                Rule::exists(Subcategory::class, 'id')->where(function ($query) use ($project) {
                    $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('project_id', $project?->id));
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('auxiliaries', 'name')
                    ->where(fn ($query) => $query->where('subcategory_id', $this->input('subcategory_id')))
                    ->ignore($auxiliary?->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
