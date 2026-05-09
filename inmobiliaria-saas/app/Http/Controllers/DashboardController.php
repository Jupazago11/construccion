<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $companyId = $user->company_id;

        $stats = $user->isSuperAdmin()
            ? $this->superAdminStats()
            : $this->companyStats($companyId);

        return view('dashboard', [
            'stats' => $stats,
            'currentUser' => $user,
        ]);
    }

    protected function superAdminStats(): array
    {
        return [
            'companies' => Company::query()->where('status', '!=', EntityStatus::Deleted->value)->count(),
            'active_companies' => Company::query()->where('status', EntityStatus::Active->value)->count(),
            'users' => User::query()->where('status', '!=', EntityStatus::Deleted->value)->count(),
            'projects' => Project::query()->where('status', '!=', EntityStatus::Deleted->value)->count(),
            'expenses' => Expense::query()->where('status', EntityStatus::Active->value)->count(),
            'expense_total' => Expense::query()->where('status', EntityStatus::Active->value)->sum('total_amount'),
        ];
    }

    protected function companyStats(?int $companyId): array
    {
        return [
            'companies' => $companyId ? 1 : 0,
            'active_companies' => Company::query()
                ->whereKey($companyId)
                ->where('status', EntityStatus::Active->value)
                ->count(),
            'users' => User::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
            'projects' => Project::query()
                ->where('company_id', $companyId)
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->count(),
            'expenses' => Expense::query()
                ->where('company_id', $companyId)
                ->where('status', EntityStatus::Active->value)
                ->count(),
            'expense_total' => Expense::query()
                ->where('company_id', $companyId)
                ->where('status', EntityStatus::Active->value)
                ->sum('total_amount'),
        ];
    }
}
