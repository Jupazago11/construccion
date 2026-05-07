@php
    $isEditing = $managedUser->exists;
    $selectedCompany = old('company_id', $managedUser->company_id ?: request('company_id'));
    $selectedRole = old('role', $managedUser->roles->first()?->name);
@endphp

<div class="grid gap-6 md:grid-cols-2">
    @if (auth()->user()->isSuperAdmin())
        <div>
            <x-input-label for="company_id" :value="'Empresa'" />
            <select id="company_id" name="company_id" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                <option value="">Selecciona una empresa</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) $selectedCompany === (string) $company->id)>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
        </div>
    @endif

    <div>
        <x-input-label for="role" :value="'Rol'" />
        <select id="role" name="role" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
            <option value="">Selecciona un rol</option>
            @foreach ($availableRoles as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('role')" />
    </div>

    <div>
        <x-input-label for="username" :value="'Usuario'" />
        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $managedUser->username)" required />
        <p class="mt-2 text-xs text-stone-500">Usa solo letras, números, puntos, guiones o guiones bajos.</p>
        <x-input-error class="mt-2" :messages="$errors->get('username')" />
    </div>

    <div>
        <x-input-label for="name" :value="'Nombre'" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $managedUser->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="email" :value="'Correo'" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $managedUser->email)" required />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="status" :value="'Estado'" />
        <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
            @foreach (['active', 'inactive', 'deleted'] as $status)
                <option value="{{ $status }}" @selected(old('status', $managedUser->status ?: 'active') === $status)>
                    {{ ['active' => 'Activo', 'inactive' => 'Inactivo', 'deleted' => 'Eliminado'][$status] }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="password" :value="$isEditing ? 'Nueva contraseña' : 'Contraseña'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $isEditing" />
        <x-input-error class="mt-2" :messages="$errors->get('password')" />
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="$isEditing ? 'Confirmar nueva contraseña' : 'Confirmar contraseña'" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $isEditing" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ $isEditing ? route('users.show', $managedUser) : route('users.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
        Cancelar
    </a>
    <x-primary-button>
        {{ $isEditing ? 'Actualizar usuario' : 'Crear usuario' }}
    </x-primary-button>
</div>
