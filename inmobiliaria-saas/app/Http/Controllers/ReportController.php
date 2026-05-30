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

        $projects = Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('company_id', $user->company_id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $expRanges = Expense::query()
            ->selectRaw('project_id, MIN(expense_date) as oldest_date')
            ->where('status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->groupBy('project_id')
            ->get()->keyBy('project_id');

        $purRanges = Purchase::query()
            ->selectRaw('project_id, MIN(purchase_date) as oldest_date')
            ->where('status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->groupBy('project_id')
            ->get()->keyBy('project_id');

        $today              = today()->format('Y-m-d');
        $requestedProjectId = $request->integer('project_id') ?: null;
        $selectedProject    = $requestedProjectId ? $projects->firstWhere('id', $requestedProjectId) : null;
        $projectId          = $selectedProject?->id;

        $oldestForProject = $projectId
            ? collect([
                $expRanges->get($projectId)?->oldest_date,
                $purRanges->get($projectId)?->oldest_date,
            ])->filter()->sort()->first()
            : null;

        $dateFrom = $projectId
            ? ($oldestForProject ? Carbon::parse($oldestForProject)->format('Y-m-d') : '')
            : $request->string('date_from')->toString();
        $dateTo = $projectId ? $today : $request->string('date_to')->toString();

        $expSub = DB::table('expenses')
            ->selectRaw('company_id, project_id, provider_id, product_id, activity_id, total_amount, expense_date as movement_date')
            ->where('status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $dateTo));

        $purSub = DB::table('purchases')
            ->selectRaw('company_id, project_id, provider_id, product_id, activity_id, total_amount, purchase_date as movement_date')
            ->where('status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($dateFrom, fn ($q) => $q->whereDate('purchase_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('purchase_date', '<=', $dateTo));

        $base = DB::query()
            ->fromRaw("({$expSub->toSql()} UNION ALL {$purSub->toSql()}) as movements")
            ->mergeBindings($expSub)
            ->mergeBindings($purSub);

        $summary = [
            'total_amount'    => (clone $base)->sum('movements.total_amount'),
            'movements_count' => (clone $base)->count(),
        ];

        $totalsByGroup = (clone $base)
            ->leftJoin('products', 'products.id', '=', 'movements.product_id')
            ->leftJoin('activities', 'activities.id', '=', 'movements.activity_id')
            ->leftJoin('product_groups', 'product_groups.id', '=', 'products.product_group_id')
            ->leftJoin('activity_groups', 'activity_groups.id', '=', 'activities.activity_group_id')
            ->selectRaw("COALESCE(activity_groups.name, product_groups.name, 'Sin grupo') as name, SUM(movements.total_amount) as movement_total, COUNT(*) as movements_count")
            ->groupByRaw("COALESCE(activity_groups.name, product_groups.name, 'Sin grupo')")
            ->orderByDesc('movement_total')
            ->get();

        $totalsBySubgroup = (clone $base)
            ->leftJoin('products', 'products.id', '=', 'movements.product_id')
            ->leftJoin('activities', 'activities.id', '=', 'movements.activity_id')
            ->leftJoin('product_subgroups', 'product_subgroups.id', '=', 'products.product_subgroup_id')
            ->leftJoin('activity_subgroups', 'activity_subgroups.id', '=', 'activities.activity_subgroup_id')
            ->selectRaw("COALESCE(activity_subgroups.name, product_subgroups.name, 'Sin subgrupo') as name, SUM(movements.total_amount) as movement_total, COUNT(*) as movements_count")
            ->groupByRaw("COALESCE(activity_subgroups.name, product_subgroups.name, 'Sin subgrupo')")
            ->orderByDesc('movement_total')
            ->get();

        $totalsByProduct = (clone $base)
            ->leftJoin('products', 'products.id', '=', 'movements.product_id')
            ->leftJoin('activities', 'activities.id', '=', 'movements.activity_id')
            ->selectRaw("COALESCE(activities.name, products.name, 'Sin ítem') as name, SUM(movements.total_amount) as movement_total, COUNT(*) as movements_count")
            ->groupByRaw("COALESCE(activities.name, products.name, 'Sin ítem')")
            ->orderByDesc('movement_total')
            ->get();

        $seriesByDate = (clone $base)
            ->selectRaw('movements.movement_date, SUM(movements.total_amount) as movement_total')
            ->groupBy('movements.movement_date')
            ->orderBy('movements.movement_date')
            ->get();

        $expHistSub = DB::table('expenses')
            ->leftJoin('products', 'products.id', '=', 'expenses.product_id')
            ->leftJoin('activities', 'activities.id', '=', 'expenses.activity_id')
            ->leftJoin('product_groups', 'product_groups.id', '=', 'products.product_group_id')
            ->leftJoin('activity_groups', 'activity_groups.id', '=', 'activities.activity_group_id')
            ->leftJoin('product_subgroups', 'product_subgroups.id', '=', 'products.product_subgroup_id')
            ->leftJoin('providers2', 'providers2.id', '=', 'expenses.provider_id')
            ->leftJoin('projects', 'projects.id', '=', 'expenses.project_id')
            ->selectRaw("
                projects.name as project_name,
                COALESCE(activities.name, products.name, 'Sin ítem') as item_name,
                COALESCE(activity_groups.name, product_groups.name, '') as group_name,
                COALESCE(product_subgroups.name, '') as subgroup_name,
                providers2.name as provider_name,
                expenses.total_amount,
                expenses.expense_date as movement_date
            ")
            ->where('expenses.status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('expenses.company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('expenses.project_id', $projectId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($dateFrom, fn ($q) => $q->whereDate('expenses.expense_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('expenses.expense_date', '<=', $dateTo));

        $purHistSub = DB::table('purchases')
            ->leftJoin('products', 'products.id', '=', 'purchases.product_id')
            ->leftJoin('activities', 'activities.id', '=', 'purchases.activity_id')
            ->leftJoin('product_groups', 'product_groups.id', '=', 'products.product_group_id')
            ->leftJoin('activity_groups', 'activity_groups.id', '=', 'activities.activity_group_id')
            ->leftJoin('product_subgroups', 'product_subgroups.id', '=', 'products.product_subgroup_id')
            ->leftJoin('providers2', 'providers2.id', '=', 'purchases.provider_id')
            ->leftJoin('projects', 'projects.id', '=', 'purchases.project_id')
            ->selectRaw("
                projects.name as project_name,
                COALESCE(activities.name, products.name, 'Sin ítem') as item_name,
                COALESCE(activity_groups.name, product_groups.name, '') as group_name,
                COALESCE(product_subgroups.name, '') as subgroup_name,
                providers2.name as provider_name,
                purchases.total_amount,
                purchases.purchase_date as movement_date
            ")
            ->where('purchases.status', EntityStatus::Active->value)
            ->when($companyId, fn ($q) => $q->where('purchases.company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('purchases.project_id', $projectId), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($dateFrom, fn ($q) => $q->whereDate('purchases.purchase_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('purchases.purchase_date', '<=', $dateTo));

        $history = DB::query()
            ->fromRaw("({$expHistSub->toSql()} UNION ALL {$purHistSub->toSql()}) as hist")
            ->mergeBindings($expHistSub)
            ->mergeBindings($purHistSub)
            ->orderByDesc('hist.movement_date')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() && $request->boolean('history_only')) {
            return response()->view('reports._history', compact('history'));
        }

        return view('reports.index', [
            'summary'             => $summary,
            'totalsByCategory'    => $totalsByGroup,
            'totalsBySubcategory' => $totalsBySubgroup,
            'totalsByAuxiliary'   => $totalsByProduct,
            'seriesByDate'        => $seriesByDate,
            'history'             => $history,
            'selectedProject'     => $selectedProject,
            'movementLabel'       => 'Gastos',
            'movementSingular'    => 'gasto',
            'companies'           => $user->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects'      => $projects,
            'projectRanges' => $projects->mapWithKeys(fn ($project) => [
                $project->id => [
                    'oldest_movement_date' => collect([
                        $expRanges->get($project->id)?->oldest_date,
                        $purRanges->get($project->id)?->oldest_date,
                    ])->filter()->sort()->first() ?? '',
                    'today' => $today,
                ],
            ]),
            'filters' => [
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
            ],
        ]);
    }
}
