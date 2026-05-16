@php
    $isSelf = $managedUser->exists && $managedUser->is(auth()->user());
    $currentRole = optional($managedUser->roles->first())->name;
    $hideRoleSelector = $managedUser->exists && $currentRole === 'CompanyAdmin';
    $canManageStatus = auth()->user()->isSuperAdmin()
        || (auth()->user()->hasRole('CompanyAdmin') && ! $isSelf && (! $managedUser->exists || $managedUser->hasAnyRole(['Operator', 'Viewer'])));
    $roleLabels = [
        'SuperAdmin' => 'Superadministrador',
        'CompanyAdmin' => 'Administrador',
        'Operator' => 'Operador',
        'Viewer' => 'Visualizador',
        'BuyerUser' => 'Usuario comprador',
    ];
@endphp

<form method="POST" action="{{ $action }}" data-ajax-form class="flex h-full min-h-0 flex-col gap-4 overflow-hidden sm:gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-6 md:grid-cols-2">
        @if (auth()->user()->isSuperAdmin())
            <div>
                <x-input-label for="company_id" :value="'Empresa'" />
                <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <option value="">Selecciona una empresa</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(($managedUser->company_id ?: request('company_id')) == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="company_id"></p>
            </div>
        @endif

        <div>
            <x-input-label for="role" :value="'Rol'" />
            @if ($hideRoleSelector)
                <input type="hidden" name="role" value="{{ $currentRole }}">
                <div class="mt-1 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                    {{ $roleLabels[$currentRole] ?? $currentRole }}
                </div>
            @else
                <select id="role" name="role" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <option value="">Selecciona un rol</option>
                    @foreach ($availableRoles as $role)
                        <option value="{{ $role }}" @selected($currentRole === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                    @endforeach
                </select>
            @endif
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="role"></p>
        </div>

        <div>
            <x-input-label for="username" :value="'Usuario'" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="$managedUser->username" required />
            <p class="mt-2 text-xs text-stone-500">Usa solo letras, numeros, puntos, guiones o guiones bajos.</p>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="username"></p>
        </div>

        <div>
            <x-input-label for="name" :value="'Nombre'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$managedUser->name" required />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
        </div>

        @if ($canManageStatus)
            <div>
                <x-input-label for="status" :value="'Estado'" />
                <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'deleted' => 'Eliminado'] as $value => $label)
                        @continue($isSelf && $value !== 'active')
                        <option value="{{ $value }}" @selected(($managedUser->status ?: 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if ($isSelf)
                    <p class="mt-2 text-xs text-stone-500">Tu propio usuario solo puede mantenerse activo desde esta vista.</p>
                @endif
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="status"></p>
            </div>
        @endif

        <div>
            <x-input-label for="password" :value="$managedUser->exists ? 'Nueva contrasena' : 'Contrasena'" />
            <div class="relative mt-1">
                <x-text-input id="password" name="password" type="password" class="block w-full pr-24" :required="! $managedUser->exists" />
                <button type="button" onclick="const input=document.getElementById('password'); if(!input) return; input.type = input.type === 'password' ? 'text' : 'password'; this.textContent = input.type === 'password' ? 'Mostrar' : 'Ocultar';" class="absolute inset-y-0 right-3 my-auto text-sm font-medium text-stone-500 transition hover:text-stone-800">Mostrar</button>
            </div>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="password"></p>
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="$managedUser->exists ? 'Confirmar nueva contrasena' : 'Confirmar contrasena'" />
            <div class="relative mt-1">
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block w-full pr-24" :required="! $managedUser->exists" />
                <button type="button" onclick="const input=document.getElementById('password_confirmation'); if(!input) return; input.type = input.type === 'password' ? 'text' : 'password'; this.textContent = input.type === 'password' ? 'Mostrar' : 'Ocultar';" class="absolute inset-y-0 right-3 my-auto text-sm font-medium text-stone-500 transition hover:text-stone-800">Mostrar</button>
            </div>
        </div>
    </div>

    <x-modal-footer>
        <button type="submit" class="app-save-button disabled:cursor-wait disabled:opacity-60">
            {{ $managedUser->exists ? 'Actualizar usuario' : 'Crear usuario' }}
        </button>
    </x-modal-footer>
</form>
