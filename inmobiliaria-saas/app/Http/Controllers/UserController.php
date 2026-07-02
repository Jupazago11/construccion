<?php

namespace App\Http\Controllers;

use App\Enums\EntityStatus;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Lista usuarios con filtros tenant y puede responder tabla/paginación parcial por AJAX.
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $authUser = $request->user();
        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $companyId = $authUser->isSuperAdmin()
            ? $request->integer('company_id') ?: null
            : $authUser->company_id;

        $users = User::query()
            ->with(['company', 'roles'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $authUser->isSuperAdmin(), fn ($query) => $query->where('status', '!=', EntityStatus::Deleted->value))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('users._table_body', compact('users'))->render(),
                'pagination_html' => $users->links('pagination::tailwind')->toHtml(),
            ]);
        }

        return view('users.index', [
            'users' => $users,
            'companies' => $authUser->isSuperAdmin()
                ? Company::query()->where('status', '!=', EntityStatus::Deleted->value)->orderBy('name')->get()
                : collect(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company_id' => $companyId,
            ],
        ]);
    }

    // Renderiza el formulario de creación de usuario con empresas y roles permitidos para el actor actual.
    public function create(Request $request): View|string
    {
        $this->authorize('create', User::class);

        $authUser = $request->user();

        if ($request->ajax()) {
            return view('users._modal_form', [
                'managedUser' => new User(),
                'companies' => $this->companiesForForm($authUser),
                'availableRoles' => $this->availableRoles($authUser),
                'action' => route('users.store'),
                'method' => 'POST',
            ])->render();
        }

        return view('users.create', [
            'managedUser' => new User(),
            'companies' => $this->companiesForForm($authUser),
            'availableRoles' => $this->availableRoles($authUser),
        ]);
    }

    // Crea un usuario, asigna su rol inicial y devuelve fila parcial cuando el flujo es AJAX.
    public function store(UserStoreRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', User::class);

        $authUser = $request->user();
        $data = $request->validated();

        $user = User::query()->create([
            'company_id' => $authUser->isSuperAdmin() ? $data['company_id'] : $authUser->company_id,
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
            'email_verified_at' => filled($data['email'] ?? null) ? now() : null,
        ]);

        $user->syncRoles([$data['role']]);
        $user->load(['company', 'roles']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $user->id,
                'row_html' => view('users._row', compact('user'))->render(),
                'message' => 'Usuario creado correctamente.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    // Muestra el detalle del usuario y sus relaciones principales.
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['company', 'roles']);

        return view('users.show', [
            'managedUser' => $user,
        ]);
    }

    // Renderiza el formulario de edición de usuario con el alcance permitido por policy.
    public function edit(Request $request, User $user): View|string
    {
        $this->authorize('update', $user);

        $authUser = $request->user();
        $user->load('roles');

        if ($request->ajax()) {
            return view('users._modal_form', [
                'managedUser' => $user,
                'companies' => $this->companiesForForm($authUser),
                'availableRoles' => $this->availableRoles($authUser),
                'action' => route('users.update', $user),
                'method' => 'PATCH',
            ])->render();
        }

        return view('users.edit', [
            'managedUser' => $user,
            'companies' => $this->companiesForForm($authUser),
            'availableRoles' => $this->availableRoles($authUser),
        ]);
    }

    // Actualiza datos, contraseña y rol del usuario respetando límites de gestión por perfil.
    public function update(UserUpdateRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $user);

        $authUser = $request->user();
        $data = $request->validated();

        $status = $this->canManageUserStatus($authUser, $user)
            ? ($data['status'] ?? $user->status)
            : $user->status;

        $this->ensureUserCanRemainActive($request, $user, $status);

        $user->update([
            'company_id' => $authUser->isSuperAdmin() ? ($data['company_id'] ?? null) : $user->company_id,
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'status' => $status,
            'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : $user->password,
            'email_verified_at' => filled($data['email'] ?? null)
                ? ($user->email === ($data['email'] ?? null) ? $user->email_verified_at : null)
                : null,
        ]);

        $user->syncRoles([$data['role']]);
        $user->load(['company', 'roles']);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $user->id,
                'row_html' => view('users._row', compact('user'))->render(),
                'message' => 'Usuario actualizado correctamente.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    // Cambia el estado del usuario validando que no se rompan reglas mínimas de acceso o administración.
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        abort_unless($this->canManageUserStatus($request->user(), $user), 403);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $this->ensureUserCanRemainActive($request, $user, $data['status']);

        $user->update([
            'status' => $data['status'],
        ]);

        $user->load(['company', 'roles']);

        return response()->json([
            'id' => $user->id,
            'row_html' => view('users._row', compact('user'))->render(),
            'message' => 'Estado del usuario actualizado correctamente.',
        ]);
    }

    // Archiva al usuario solo cuando no deja dependencias operativas incompatibles.
    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()?->is($user)) {
            $message = 'No puedes archivarte a ti mismo.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('users.index')->with('status', $message);
        }

        if ($this->userHasDependencies($user)) {
            $message = 'El usuario no puede eliminarse porque tiene dependencias registradas.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('users.index')->with('status', $message);
        }

        $user->update([
            'status' => EntityStatus::Deleted->value,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $user->id,
                'message' => 'Usuario archivado correctamente.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario archivado correctamente.');
    }

    // Devuelve la lista de roles que el usuario autenticado puede asignar en formularios.
    protected function availableRoles(User $authUser): array
    {
        return $authUser->isSuperAdmin()
            ? ['CompanyAdmin', 'Operator', 'Viewer']
            : ['Operator', 'Viewer'];
    }

    // Devuelve las empresas disponibles para formularios según el alcance del actor actual.
    protected function companiesForForm(User $authUser)
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

    // Determina si el usuario tiene relaciones activas que desaconsejan archivarlo.
    protected function userHasDependencies(User $user): bool
    {
        return $user->createdExpenses()->where('status', '!=', EntityStatus::Deleted->value)->exists()
            || $user->uploadedExpenseAttachments()->where('status', '!=', EntityStatus::Deleted->value)->exists();
    }

    // Evita dejar activos usuarios que ya no pueden autenticarse por estado propio o de su empresa.
    protected function ensureUserCanRemainActive(Request $request, User $user, string $status): void
    {
        if (! $request->user()?->is($user) || $status === EntityStatus::Active->value) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'No puedes inactivar ni eliminar tu propio usuario.',
        ]);
    }

    // Valida si el actor autenticado puede cambiar el estado del usuario objetivo.
    protected function canManageUserStatus(?User $authUser, User $managedUser): bool
    {
        if (! $authUser || $managedUser->isSuperAdmin()) {
            return false;
        }

        if ($authUser->isSuperAdmin()) {
            return true;
        }

        if (! $authUser->hasRole('CompanyAdmin') || $authUser->is($managedUser) || $authUser->company_id !== $managedUser->company_id) {
            return false;
        }

        return $managedUser->hasAnyRole(['Operator', 'Viewer']);
    }
}
