<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;
        $projectId = $request->integer('project_id') ?: null;
        $event = $request->string('event')->toString();
        $logName = $request->string('log_name')->toString();
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();

        $activities = Activity::query()
            ->with(['causer', 'subject', 'company', 'project'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($logName !== '', fn ($query) => $query->where('log_name', $logName))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit.index', [
            'activities' => $activities,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'projects' => Project::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('company_id', $authUser->company_id))
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->orderBy('name')
                ->get(),
            'filters' => [
                'company_id' => $companyId,
                'project_id' => $projectId,
                'event' => $event,
                'log_name' => $logName,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->sort()->values(),
            'events' => Activity::query()->distinct()->pluck('event')->filter()->sort()->values(),
        ]);
    }
}
