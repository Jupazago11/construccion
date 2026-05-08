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
    public function index(Request $request): View
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
            ->paginate(12)
            ->withQueryString();

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

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['company', 'roles']);

        return view('users.show', [
            'managedUser' => $user,
        ]);
    }

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

    public function update(UserUpdateRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $user);

        $authUser = $request->user();
        $data = $request->validated();

        $status = $authUser->isSuperAdmin()
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

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:active,inactive,deleted'],
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

    protected function availableRoles(User $authUser): array
    {
        return $authUser->isSuperAdmin()
            ? ['CompanyAdmin', 'Operator', 'Viewer']
            : ['Operator', 'Viewer'];
    }

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

    protected function userHasDependencies(User $user): bool
    {
        return $user->createdExpenses()->count() > 0
            || $user->uploadedExpenseAttachments()->count() > 0;
    }

    protected function ensureUserCanRemainActive(Request $request, User $user, string $status): void
    {
        if (! $request->user()?->is($user) || $status === EntityStatus::Active->value) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'No puedes inactivar ni eliminar tu propio usuario.',
        ]);
    }
}
