<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Project;
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
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('company_id', $user->company_id))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $projectRanges = Expense::query()
            ->selectRaw('project_id, MIN(expense_date) as oldest_expense_date')
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
        $oldestExpenseDate = $selectedProjectRange?->oldest_expense_date
            ? Carbon::parse($selectedProjectRange->oldest_expense_date)->format('Y-m-d')
            : '';
        $today = today()->format('Y-m-d');

        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        if ($projectId) {
            $dateFrom = $oldestExpenseDate !== '' ? $oldestExpenseDate : $dateFrom;
            $dateTo = $today;
        }

        $baseQuery = Expense::query()
            ->where('expenses.status', EntityStatus::Active->value)
            ->when($companyId, fn ($query) => $query->where('expenses.company_id', $companyId))
            ->when(
                $projectId,
                fn ($query) => $query->where('expenses.project_id', $projectId),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('expenses.expense_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('expenses.expense_date', '<=', $dateTo));

        $detailQuery = clone $baseQuery;

        $summary = [
            'total_amount' => (clone $baseQuery)->sum('expenses.total_amount'),
            'expenses_count' => (clone $baseQuery)->count(),
            'projects_count' => (clone $baseQuery)->distinct('expenses.project_id')->count('expenses.project_id'),
            'average_ticket' => (clone $baseQuery)->avg('expenses.total_amount') ?: 0,
        ];

        $totalsByCategory = (clone $detailQuery)
            ->join('categories', 'categories.id', '=', 'expenses.category_id')
            ->selectRaw('categories.id, categories.name, SUM(expenses.total_amount) as total_amount, COUNT(expenses.id) as expenses_count')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $totalsBySubcategory = (clone $detailQuery)
            ->join('subcategories', 'subcategories.id', '=', 'expenses.subcategory_id')
            ->selectRaw('subcategories.id, subcategories.name, SUM(expenses.total_amount) as total_amount, COUNT(expenses.id) as expenses_count')
            ->groupBy('subcategories.id', 'subcategories.name')
            ->orderByDesc('total_amount')
            ->get();

        $totalsByAuxiliary = (clone $detailQuery)
            ->leftJoin('auxiliaries', 'auxiliaries.id', '=', 'expenses.auxiliary_id')
            ->selectRaw("COALESCE(auxiliaries.id, 0) as auxiliary_group_id, COALESCE(auxiliaries.name, 'Sin auxiliar') as name, SUM(expenses.total_amount) as total_amount, COUNT(expenses.id) as expenses_count")
            ->groupBy(DB::raw("COALESCE(auxiliaries.id, 0), COALESCE(auxiliaries.name, 'Sin auxiliar')"))
            ->orderByDesc('total_amount')
            ->get();

        $seriesByDate = (clone $detailQuery)
            ->selectRaw('expenses.expense_date, SUM(expenses.total_amount) as total_amount')
            ->groupBy('expenses.expense_date')
            ->orderBy('expenses.expense_date')
            ->get();

        $history = (clone $detailQuery)
            ->with(['project', 'category', 'subcategory', 'auxiliary', 'provider'])
            ->latest('expenses.expense_date')
            ->latest('expenses.id')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() && $request->boolean('history_only')) {
            return response()->view('reports._history', [
                'history' => $history,
            ]);
        }

        return view('reports.index', [
            'summary' => $summary,
            'totalsByCategory' => $totalsByCategory,
            'totalsBySubcategory' => $totalsBySubcategory,
            'totalsByAuxiliary' => $totalsByAuxiliary,
            'seriesByDate' => $seriesByDate,
            'history' => $history,
            'selectedProject' => $selectedProject,
            'companies' => $user->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => $projects,
            'projectRanges' => $projects->mapWithKeys(fn ($project) => [
                $project->id => [
                    'oldest_expense_date' => $projectRanges->get($project->id)?->oldest_expense_date
                        ? Carbon::parse($projectRanges->get($project->id)->oldest_expense_date)->format('Y-m-d')
                        : '',
                    'today' => $today,
                ],
            ]),
            'filters' => [
                'company_id' => $companyId,
                'project_id' => $projectId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
