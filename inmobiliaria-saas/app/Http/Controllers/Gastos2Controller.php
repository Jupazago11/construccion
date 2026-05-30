<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class Gastos2Controller extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $authUser  = $request->user();
        $search    = trim((string) $request->string('search'));
        $projectId = $request->integer('project_id') ?: null;
        $companyId = $authUser->isSuperAdmin()
            ? ($request->integer('company_id') ?: null)
            : $authUser->company_id;
        $dateFrom  = $request->string('date_from')->toString();
        $dateTo    = $request->string('date_to')->toString();
        $status    = in_array($request->string('status')->toString(), ['open', 'closed'], true)
            ? $request->string('status')->toString()
            : '';

        $invoices = Invoice::query()
            ->with(['project', 'provider'])
            ->whereIn('type', ['expense', 'purchase'])
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('invoice_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('invoice_date', '<=', $dateTo))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'ilike', "%{$search}%")
                        ->orWhere('description', 'ilike', "%{$search}%")
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'ilike', "%{$search}%"))
                        ->orWhereHas('provider', fn ($p) => $p->where('name', 'ilike', "%{$search}%"));
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html'      => view('gastos2._table_body', compact('invoices'))->render(),
                'pagination_html' => ViewFacade::make('pagination::tailwind', ['paginator' => $invoices])->render(),
            ]);
        }

        $projects = \App\Models\Project::query()
            ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('company_id', $authUser->company_id))
            ->whereNotIn('status', ['cancelled', EntityStatus::Deleted->value])
            ->orderBy('name')
            ->get();

        $companies = $authUser->isSuperAdmin()
            ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
            : collect();

        return view('gastos2.index', [
            'invoices'  => $invoices,
            'projects'  => $projects,
            'companies' => $companies,
            'filters'   => [
                'search'     => $search,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
                'status'     => $status,
            ],
        ]);
    }
}
