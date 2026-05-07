<?php

namespace App\Http\Requests;

use App\Enums\EntityStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectCategoryCopyRequest extends FormRequest
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
            'source_project_id' => [
                'required',
                'integer',
                Rule::exists(Project::class, 'id')->where(function ($query) use ($project) {
                    $query
                        ->where('company_id', $project?->company_id)
                        ->where('id', '!=', $project?->id)
                        ->where('status', '!=', EntityStatus::Deleted->value);
                }),
            ],
        ];
    }
}
