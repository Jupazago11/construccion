<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    // Muestra el dashboard exclusivo del superadmin y redirige al resto de roles a su home operativo.
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user->isSuperAdmin()) {
            return redirect()->route($user->homeRouteName());
        }

        $companyId = $user->company_id;

        $stats = $this->superAdminStats();

        return view('dashboard', [
            'stats' => $stats,
            'currentUser' => $user,
        ]);
    }

    // Reúne los indicadores globales visibles en el tablero de administración general.
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

    // Conserva una variante de métricas por empresa para futuras vistas tenant-específicas.
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
