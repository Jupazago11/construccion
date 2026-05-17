<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\ProductGroupStoreRequest;
use App\Http\Requests\ProductGroupUpdateRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductSubgroupStoreRequest;
use App\Http\Requests\ProductSubgroupUpdateRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductSubgroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCatalogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', ProductGroup::class);

        $payload = $this->viewPayload($request);

        if ($request->ajax() && $request->boolean('table_only')) {
            return response()->json([
                'table_html' => view('product-catalog._products_table', [
                    'products' => $payload['products'],
                ])->render(),
            ]);
        }

        return view('product-catalog.index', $payload);
    }

    public function storeGroup(ProductGroupStoreRequest $request): JsonResponse
    {
        $this->authorize('create', ProductGroup::class);

        ProductGroup::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse($request, 'Grupo creado correctamente.');
    }

    public function updateGroup(ProductGroupUpdateRequest $request, ProductGroup $productGroup): JsonResponse
    {
        $this->authorize('update', $productGroup);

        $productGroup->update(['name' => $request->validated('name')]);

        return $this->catalogResponse($request, 'Grupo actualizado correctamente.');
    }

    public function statusGroup(Request $request, ProductGroup $productGroup): JsonResponse
    {
        $this->authorize('update', $productGroup);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        $productGroup->update(['status' => $data['status']]);

        return $this->catalogResponse($request, 'Estado del grupo actualizado correctamente.');
    }

    public function destroyGroup(Request $request, ProductGroup $productGroup): JsonResponse
    {
        $this->authorize('delete', $productGroup);

        if ($productGroup->products()->where(fn ($query) => $query->whereHas('expenses')->orWhereHas('purchases'))->exists()) {
            return response()->json(['message' => 'El grupo no puede archivarse porque tiene productos usados en movimientos.'], 422);
        }

        DB::transaction(function () use ($productGroup) {
            $productGroup->products()->update(['status' => EntityStatus::Deleted->value]);
            $productGroup->subgroups()->update(['status' => EntityStatus::Deleted->value]);
            $productGroup->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->catalogResponse($request, 'Grupo archivado correctamente.');
    }

    public function storeSubgroup(ProductSubgroupStoreRequest $request): JsonResponse
    {
        $this->authorize('create', ProductSubgroup::class);

        ProductSubgroup::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'product_group_id' => $request->validated('product_group_id'),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse($request, 'Subgrupo creado correctamente.');
    }

    public function updateSubgroup(ProductSubgroupUpdateRequest $request, ProductSubgroup $productSubgroup): JsonResponse
    {
        $this->authorize('update', $productSubgroup);

        $productSubgroup->update($request->validated());

        return $this->catalogResponse($request, 'Subgrupo actualizado correctamente.');
    }

    public function statusSubgroup(Request $request, ProductSubgroup $productSubgroup): JsonResponse
    {
        $this->authorize('update', $productSubgroup);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        DB::transaction(function () use ($productSubgroup, $data) {
            $productSubgroup->update(['status' => $data['status']]);

            if ($data['status'] === EntityStatus::Inactive->value) {
                $productSubgroup->products()
                    ->where('status', '!=', EntityStatus::Deleted->value)
                    ->update(['status' => EntityStatus::Inactive->value]);
            }
        });

        return $this->catalogResponse(
            $request,
            $data['status'] === EntityStatus::Inactive->value
                ? 'Subgrupo inactivado correctamente. Sus productos también quedaron inactivos.'
                : 'Estado del subgrupo actualizado correctamente.'
        );
    }

    public function destroySubgroup(Request $request, ProductSubgroup $productSubgroup): JsonResponse
    {
        $this->authorize('delete', $productSubgroup);

        if ($productSubgroup->products()->where(fn ($query) => $query->whereHas('expenses')->orWhereHas('purchases'))->exists()) {
            return response()->json(['message' => 'El subgrupo no puede archivarse porque tiene productos usados en movimientos.'], 422);
        }

        DB::transaction(function () use ($productSubgroup) {
            $productSubgroup->products()->update(['status' => EntityStatus::Deleted->value]);
            $productSubgroup->update(['status' => EntityStatus::Deleted->value]);
        });

        return $this->catalogResponse($request, 'Subgrupo archivado correctamente. Sus productos también fueron archivados.');
    }

    public function storeProduct(ProductStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        Product::query()->create([
            'company_id' => $request->resolvedCompanyId(),
            'product_group_id' => $request->validated('product_group_id'),
            'product_subgroup_id' => $request->validated('product_subgroup_id'),
            'name' => $request->validated('name'),
            'status' => EntityStatus::Active->value,
        ]);

        return $this->catalogResponse($request, 'Producto creado correctamente.');
    }

    public function updateProduct(ProductUpdateRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return $this->catalogResponse($request, 'Producto actualizado correctamente.');
    }

    public function statusProduct(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);

        $product->update(['status' => $data['status']]);

        return $this->catalogResponse($request, 'Estado del producto actualizado correctamente.');
    }

    public function destroyProduct(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        if ($product->expenses()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $product->purchases()->where('status', '!=', EntityStatus::Deleted->value)->exists()) {
            return response()->json(['message' => 'El producto no puede archivarse porque ya tiene gastos o compras.'], 422);
        }

        $product->update(['status' => EntityStatus::Deleted->value]);

        return $this->catalogResponse($request, 'Producto archivado correctamente.');
    }

    protected function viewPayload(Request $request): array
    {
        $authUser = $request->user();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $filters = [
            'company_id'  => $companyId,
            'search'      => trim($request->input('search', '')),
            'group_id'    => $request->integer('group_id') ?: null,
            'subgroup_id' => $request->integer('subgroup_id') ?: null,
        ];

        $groups = ProductGroup::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $subgroups = ProductSubgroup::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', EntityStatus::Deleted->value)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with(['group', 'subgroup'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($q) => $q->where('status', '!=', EntityStatus::Deleted->value))
            ->when($filters['search'], fn ($q) => $q->where('name', 'ilike', '%' . $filters['search'] . '%'))
            ->when($filters['group_id'], fn ($q) => $q->where('product_group_id', $filters['group_id']))
            ->when($filters['subgroup_id'], fn ($q) => $q->where('product_subgroup_id', $filters['subgroup_id']))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return [
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'filters'   => $filters,
            'groups'    => $groups,
            'subgroups' => $subgroups,
            'products'  => $products,
        ];
    }

    protected function catalogResponse(Request $request, string $message): JsonResponse
    {
        return response()->json(['message' => $message]);
    }
}
