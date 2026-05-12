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
                $activeCategory = $this->activeCategoryByName($destinationProject, $sourceCategory->name);

                if ($activeCategory) {
                    $this->copyMissingSubcategories($sourceCategory, $activeCategory, $counts);
                    continue;
                }

                $category = Category::query()->create([
                    'project_id' => $destinationProject->id,
                    'name' => $this->nextCategoryName($destinationProject, $sourceCategory->name),
                    'description' => $sourceCategory->description,
                    'sort_order' => $sourceCategory->sort_order,
                    'status' => $sourceCategory->status,
                ]);

                $counts['categories']++;

                $this->copyMissingSubcategories($sourceCategory, $category, $counts);
            }

            return $counts;
        });
    }

    protected function copyMissingSubcategories(Category $sourceCategory, Category $destinationCategory, array &$counts): void
    {
        foreach ($sourceCategory->subcategories as $sourceSubcategory) {
            $activeSubcategory = $this->activeSubcategoryByName($destinationCategory, $sourceSubcategory->name);

            if ($activeSubcategory) {
                $this->copyMissingAuxiliaries($sourceSubcategory, $activeSubcategory, $counts);
                continue;
            }

            $subcategory = Subcategory::query()->create([
                'category_id' => $destinationCategory->id,
                'name' => $this->nextSubcategoryName($destinationCategory, $sourceSubcategory->name),
                'description' => $sourceSubcategory->description,
                'sort_order' => $sourceSubcategory->sort_order,
                'status' => $sourceSubcategory->status,
            ]);

            $counts['subcategories']++;

            $this->copyMissingAuxiliaries($sourceSubcategory, $subcategory, $counts);
        }
    }

    protected function copyMissingAuxiliaries(Subcategory $sourceSubcategory, Subcategory $destinationSubcategory, array &$counts): void
    {
        foreach ($sourceSubcategory->auxiliaries as $sourceAuxiliary) {
            if ($this->activeAuxiliaryByName($destinationSubcategory, $sourceAuxiliary->name)) {
                continue;
            }

            Auxiliary::query()->create([
                'subcategory_id' => $destinationSubcategory->id,
                'name' => $this->nextAuxiliaryName($destinationSubcategory, $sourceAuxiliary->name),
                'description' => $sourceAuxiliary->description,
                'sort_order' => $sourceAuxiliary->sort_order,
                'status' => $sourceAuxiliary->status,
            ]);

            $counts['auxiliaries']++;
        }
    }

    protected function activeCategoryByName(Project $project, string $name): ?Category
    {
        return Category::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->first();
    }

    protected function activeSubcategoryByName(Category $category, string $name): ?Subcategory
    {
        return Subcategory::query()
            ->where('category_id', $category->id)
            ->where('name', $name)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->first();
    }

    protected function activeAuxiliaryByName(Subcategory $subcategory, string $name): ?Auxiliary
    {
        return Auxiliary::query()
            ->where('subcategory_id', $subcategory->id)
            ->where('name', $name)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->first();
    }

    protected function nextCategoryName(Project $project, string $baseName): string
    {
        $this->releaseDeletedName(function (string $candidate) use ($project) {
            return Category::query()
                ->where('project_id', $project->id)
                ->where('name', $candidate)
                ->where('status', EntityStatus::Deleted->value)
                ->first();
        }, function (string $candidate) use ($project) {
            return Category::query()
                ->where('project_id', $project->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);

        return $this->uniqueName(function (string $candidate) use ($project) {
            return Category::query()
                ->where('project_id', $project->id)
                ->where('name', $candidate)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->exists();
        }, $baseName);
    }

    protected function nextSubcategoryName(Category $category, string $baseName): string
    {
        $this->releaseDeletedName(function (string $candidate) use ($category) {
            return Subcategory::query()
                ->where('category_id', $category->id)
                ->where('name', $candidate)
                ->where('status', EntityStatus::Deleted->value)
                ->first();
        }, function (string $candidate) use ($category) {
            return Subcategory::query()
                ->where('category_id', $category->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);

        return $this->uniqueName(function (string $candidate) use ($category) {
            return Subcategory::query()
                ->where('category_id', $category->id)
                ->where('name', $candidate)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->exists();
        }, $baseName);
    }

    protected function nextAuxiliaryName(Subcategory $subcategory, string $baseName): string
    {
        $this->releaseDeletedName(function (string $candidate) use ($subcategory) {
            return Auxiliary::query()
                ->where('subcategory_id', $subcategory->id)
                ->where('name', $candidate)
                ->where('status', EntityStatus::Deleted->value)
                ->first();
        }, function (string $candidate) use ($subcategory) {
            return Auxiliary::query()
                ->where('subcategory_id', $subcategory->id)
                ->where('name', $candidate)
                ->exists();
        }, $baseName);

        return $this->uniqueName(function (string $candidate) use ($subcategory) {
            return Auxiliary::query()
                ->where('subcategory_id', $subcategory->id)
                ->where('name', $candidate)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->exists();
        }, $baseName);
    }

    protected function releaseDeletedName(callable $findDeleted, callable $exists, string $baseName): void
    {
        $deletedRecord = $findDeleted($baseName);

        if (! $deletedRecord) {
            return;
        }

        $deletedRecord->update([
            'name' => $this->uniqueName($exists, "{$baseName} (eliminado)"),
        ]);
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
