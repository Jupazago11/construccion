<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): View|Response
    {
        $user = $request->user();

        abort_unless($user->hasPermissionTo('reports.view'), 403);

        $companyId = $user->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $user->company_id;
        $reportType = $request->string('report_type')->toString() === 'purchase' ? 'purchase' : 'expense';
        $modelClass = $reportType === 'purchase' ? Purchase::class : Expense::class;
        $table = $reportType === 'purchase' ? 'purchases' : 'expenses';
        $dateColumn = $reportType === 'purchase' ? 'purchase_date' : 'expense_date';
        $movementLabel = $reportType === 'purchase' ? 'Compras' : 'Gastos';
        $movementSingular = $reportType === 'purchase' ? 'compra' : 'gasto';
        $projects = Project::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('company_id', $user->company_id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $projectRanges = $modelClass::query()
            ->selectRaw("project_id, MIN({$dateColumn}) as oldest_movement_date")
            ->where('status', EntityStatus::Active->value)
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('company_id', $user->company_id))
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $requestedProjectId = $request->integer('project_id') ?: null;
        $selectedProject = $requestedProjectId
            ? $projects->firstWhere('id', $requestedProjectId)
            : null;
        $projectId = $selectedProject?->id;

        $selectedProjectRange = $projectId ? $projectRanges->get($projectId) : null;
        $oldestMovementDate = $selectedProjectRange?->oldest_movement_date
            ? Carbon::parse($selectedProjectRange->oldest_movement_date)->format('Y-m-d')
            : '';
        $today = today()->format('Y-m-d');

        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        if ($projectId) {
            $dateFrom = $oldestMovementDate !== '' ? $oldestMovementDate : $dateFrom;
            $dateTo = $today;
        }

        $baseQuery = $modelClass::query()
            ->where("{$table}.status", EntityStatus::Active->value)
            ->when($companyId, fn ($query) => $query->where("{$table}.company_id", $companyId))
            ->when(
                $projectId,
                fn ($query) => $query->where("{$table}.project_id", $projectId),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->when($dateFrom !== '', fn ($query) => $query->whereDate("{$table}.{$dateColumn}", '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate("{$table}.{$dateColumn}", '<=', $dateTo));

        $detailQuery = clone $baseQuery;

        $summary = [
            'total_amount' => (clone $baseQuery)->sum("{$table}.total_amount"),
            'movements_count' => (clone $baseQuery)->count(),
            'projects_count' => (clone $baseQuery)->distinct("{$table}.project_id")->count("{$table}.project_id"),
            'average_ticket' => (clone $baseQuery)->avg("{$table}.total_amount") ?: 0,
        ];

        $totalsByGroup = (clone $detailQuery)
            ->leftJoin('products', 'products.id', '=', "{$table}.product_id")
            ->leftJoin('product_groups', 'product_groups.id', '=', 'products.product_group_id')
            ->selectRaw("COALESCE(product_groups.id, 0) as group_id, COALESCE(product_groups.name, 'Sin grupo') as name, SUM({$table}.total_amount) as movement_total, COUNT({$table}.id) as movements_count")
            ->groupBy('product_groups.id', 'product_groups.name')
            ->orderByDesc('movement_total')
            ->get();

        $totalsBySubgroup = (clone $detailQuery)
            ->leftJoin('products', 'products.id', '=', "{$table}.product_id")
            ->leftJoin('product_subgroups', 'product_subgroups.id', '=', 'products.product_subgroup_id')
            ->selectRaw("COALESCE(product_subgroups.id, 0) as subgroup_id, COALESCE(product_subgroups.name, 'Sin subgrupo') as name, SUM({$table}.total_amount) as movement_total, COUNT({$table}.id) as movements_count")
            ->groupBy('product_subgroups.id', 'product_subgroups.name')
            ->orderByDesc('movement_total')
            ->get();

        $totalsByProduct = (clone $detailQuery)
            ->leftJoin('products', 'products.id', '=', "{$table}.product_id")
            ->selectRaw("COALESCE(products.id, 0) as product_id, COALESCE(products.name, 'Sin producto') as name, SUM({$table}.total_amount) as movement_total, COUNT({$table}.id) as movements_count")
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('movement_total')
            ->get();

        $seriesByDate = (clone $detailQuery)
            ->selectRaw("{$table}.{$dateColumn} as movement_date, SUM({$table}.total_amount) as movement_total")
            ->groupBy("{$table}.{$dateColumn}")
            ->orderBy("{$table}.{$dateColumn}")
            ->get();

        $history = (clone $detailQuery)
            ->with(['project', 'product.group', 'product.subgroup', 'provider'])
            ->latest("{$table}.{$dateColumn}")
            ->latest("{$table}.id")
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() && $request->boolean('history_only')) {
            return response()->view('reports._history', [
                'history' => $history,
                'reportType' => $reportType,
            ]);
        }

        return view('reports.index', [
            'summary' => $summary,
            'totalsByCategory' => $totalsByGroup,
            'totalsBySubcategory' => $totalsBySubgroup,
            'totalsByAuxiliary' => $totalsByProduct,
            'seriesByDate' => $seriesByDate,
            'history' => $history,
            'selectedProject' => $selectedProject,
            'reportType' => $reportType,
            'movementLabel' => $movementLabel,
            'movementSingular' => $movementSingular,
            'dateColumn' => $dateColumn,
            'companies' => $user->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => $projects,
            'projectRanges' => $projects->mapWithKeys(fn ($project) => [
                $project->id => [
                    'oldest_movement_date' => $projectRanges->get($project->id)?->oldest_movement_date
                        ? Carbon::parse($projectRanges->get($project->id)->oldest_movement_date)->format('Y-m-d')
                        : '',
                    'today' => $today,
                ],
            ]),
            'filters' => [
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'report_type' => $reportType,
            ],
        ]);
    }
}
