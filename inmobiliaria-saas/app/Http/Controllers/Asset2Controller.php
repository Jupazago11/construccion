<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\Asset2StoreRequest;
use App\Http\Requests\Asset2UpdateRequest;
use App\Models\Asset2;
use App\Models\Asset2Type;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Asset2Controller extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Asset2::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $baseQuery = $this->buildIndexBaseQuery($request, $companyId, $search);

        $assets2 = (clone $baseQuery)
            ->with(['company', 'type'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset2_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('assets2.index', [
            'assets2' => $assets2,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'summary' => $this->resolveSummary($baseQuery),
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
            ],
        ]);
    }

    public function create(Request $request): View|string|RedirectResponse
    {
        $this->authorize('create', Asset2::class);

        if ($request->ajax()) {
            return view('assets2._modal_form', [
                'asset2' => new Asset2(['purchase_date' => today()]),
                'companies' => $this->companiesForForm($request->user()),
                'asset2Types' => $this->asset2TypesForForm($request, $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id),
                'action' => route('assets2.store', $request->query()),
                'method' => 'POST',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'La creación de activos 2 se realiza desde la vista principal.');
    }

    public function store(Asset2StoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Asset2::class);

        $authUser = $request->user();
        $data = $request->validated();

        $asset2 = Asset2::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'name' => $data['name'],
            'asset2_type_id' => $data['asset2_type_id'],
            'asset2_type' => Asset2Type::query()->find($data['asset2_type_id'])?->name ?? '',
            'asset_condition' => $data['asset_condition'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
            'status' => EntityStatus::Active->value,
        ]);

        $this->loadAsset2ListRelations($asset2);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'row_html' => view('assets2._row', compact('asset2'))->render(),
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 creado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 creado correctamente.');
    }

    public function edit(Request $request, Asset2 $asset2): View|string|RedirectResponse
    {
        $this->authorize('update', $asset2);

        if ($request->ajax()) {
            return view('assets2._modal_form', [
                'asset2' => $asset2,
                'companies' => $this->companiesForForm($request->user()),
                'asset2Types' => $this->asset2TypesForForm($request, $asset2->company_id),
                'action' => route('assets2.update', ['asset2' => $asset2] + $request->query()),
                'method' => 'PATCH',
            ])->render();
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'La edición de activos 2 se realiza desde la vista principal.');
    }

    public function update(Asset2UpdateRequest $request, Asset2 $asset2): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $asset2);

        $authUser = $request->user();
        $data = $request->validated();

        $asset2->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? $asset2->company_id) : $asset2->company_id,
            'name' => $data['name'],
            'asset2_type_id' => $data['asset2_type_id'],
            'asset2_type' => Asset2Type::query()->find($data['asset2_type_id'])?->name ?? $asset2->asset2_type,
            'asset_condition' => $data['asset_condition'],
            'purchase_value' => (float) ($data['purchase_value'] ?? 0),
            'purchase_date' => $data['purchase_date'] ?? null,
        ]);

        $this->loadAsset2ListRelations($asset2);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'row_html' => view('assets2._row', compact('asset2'))->render(),
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $authUser->isSuperAdmin() ? $request->integer('company_id') ?: null : $authUser->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 actualizado correctamente.');
    }

    public function destroy(Request $request, Asset2 $asset2): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $asset2);

        $asset2->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $asset2->id,
                'summary_html' => view('assets2._summary', [
                    'summary' => $this->resolveSummary($this->buildIndexBaseQuery(
                        $request,
                        $request->user()->isSuperAdmin() ? $request->integer('company_id') ?: null : $request->user()->company_id,
                        trim((string) $request->string('search'))
                    )),
                ])->render(),
                'message' => 'Activo 2 archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('assets2.index')
            ->with('status', 'Activo 2 archivado correctamente.');
    }

    protected function companiesForForm($authUser)
    {
        if ($authUser->isSuperAdmin()) {
            return Company::query()
                ->where('status', '!=', EntityStatus::Deleted->value)
                ->orderBy('name')
                ->get();
        }

        return Company::query()
            ->whereKey($authUser->company_id)
            ->get();
    }

    protected function loadAsset2ListRelations(Asset2 $asset2): void
    {
        $asset2->load(['company', 'type']);
    }

    protected function buildIndexBaseQuery(Request $request, ?int $companyId, string $search): Builder
    {
        return Asset2::query()
            ->when($companyId, fn (Builder $query) => $query->where('company_id', $companyId))
            ->when(! $request->user()->isSuperAdmin(), fn (Builder $query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('asset2_type', 'like', "%{$search}%")
                        ->orWhereHas('type', fn (Builder $typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('asset_condition', 'like', "%{$search}%");
                });
            });
    }

    protected function resolveSummary(Builder $baseQuery): array
    {
        return [
            'assets2_purchase_total' => (clone $baseQuery)->sum('purchase_value'),
            'assets2_count' => (clone $baseQuery)->count(),
        ];
    }

    protected function asset2TypesForForm(Request $request, ?int $companyId)
    {
        if (! $companyId) {
            return collect();
        }

        return Asset2Type::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->withCount([
                'assets as active_assets_count' => fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Asset2Type $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'status' => $type->status,
                'can_delete' => ((int) $type->active_assets_count) === 0,
                'update_url' => route('asset2-types.update', $type),
                'delete_url' => route('asset2-types.destroy', $type),
            ])
            ->values();
    }
}
