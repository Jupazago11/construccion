<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Models\Auxiliary;
use App\Models\Category;
use App\Models\Project;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;

class ProjectCategoryReplicator
{
    public function copy(Project $sourceProject, Project $destinationProject): array
    {
        return DB::transaction(function () use ($sourceProject, $destinationProject) {
            $counts = [
                'categories' => 0,
                'subcategories' => 0,
                'auxiliaries' => 0,
            ];

            $sourceProject->load([
                'categories' => fn ($categoryQuery) => $categoryQuery
                    ->where('status', '!=', EntityStatus::Deleted->value)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'subcategories' => fn ($subcategoryQuery) => $subcategoryQuery
                            ->where('status', '!=', EntityStatus::Deleted->value)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->with([
                                'auxiliaries' => fn ($auxiliaryQuery) => $auxiliaryQuery
                                    ->where('status', '!=', EntityStatus::Deleted->value)
                                    ->orderBy('sort_order')
                                    ->orderBy('name'),
                            ]),
                    ]),
            ]);

            foreach ($sourceProject->categories as $sourceCategory) {
                $category = Category::query()->create([
                    'project_id' => $destinationProject->id,
                    'name' => $this->nextCategoryName($destinationProject, $sourceCategory->name),
                    'description' => $sourceCategory->description,
                    'sort_order' => $sourceCategory->sort_order,
                    'status' => $sourceCategory->status,
                ]);

                $counts['categories']++;

                foreach ($sourceCategory->subcategories as $sourceSubcategory) {
                    $subcategory = Subcategory::query()->create([
                        'category_id' => $category->id,
                        'name' => $this->nextSubcategoryName($category, $sourceSubcategory->name),
                        'description' => $sourceSubcategory->description,
                        'sort_order' => $sourceSubcategory->sort_order,
                        'status' => $sourceSubcategory->status,
                    ]);

                    $counts['subcategories']++;

                    foreach ($sourceSubcategory->auxiliaries as $sourceAuxiliary) {
                        Auxiliary::query()->create([
                            'subcategory_id' => $subcategory->id,
                            'name' => $this->nextAuxiliaryName($subcategory, $sourceAuxiliary->name),
                            'description' => $sourceAuxiliary->description,
                            'sort_order' => $sourceAuxiliary->sort_order,
                            'status' => $sourceAuxiliary->status,
                        ]);

                        $counts['auxiliaries']++;
                    }
                }
            }

            return $counts;
        });
    }

    protected function nextCategoryName(Project $project, string $baseName): string
    {
        return $this->uniqueName(function (string $candidate) use ($project) {
            return Category::query()
                ->where('project_id', $project->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);
    }

    protected function nextSubcategoryName(Category $category, string $baseName): string
    {
        return $this->uniqueName(function (string $candidate) use ($category) {
            return Subcategory::query()
                ->where('category_id', $category->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);
    }

    protected function nextAuxiliaryName(Subcategory $subcategory, string $baseName): string
    {
        return $this->uniqueName(function (string $candidate) use ($subcategory) {
            return Auxiliary::query()
                ->where('subcategory_id', $subcategory->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);
    }

    protected function uniqueName(callable $exists, string $baseName): string
    {
        $candidate = $baseName;
        $suffix = 0;

        while ($exists($candidate)) {
            $suffix++;
            $candidate = sprintf('%s (copy %d)', $baseName, $suffix);
        }

        return $candidate;
    }
}
