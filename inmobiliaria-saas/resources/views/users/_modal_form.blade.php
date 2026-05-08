@php
    $isSelf = $managedUser->exists && $managedUser->is(auth()->user());
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
            <select id="role" name="role" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                <option value="">Selecciona un rol</option>
                @foreach ($availableRoles as $role)
                    <option value="{{ $role }}" @selected(optional($managedUser->roles->first())->name === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="role"></p>
        </div>

        <div>
            <x-input-label for="username" :value="'Usuario'" />
            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="$managedUser->username" required />
            <p class="mt-2 text-xs text-stone-500">Usa solo letras, números, puntos, guiones o guiones bajos.</p>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="username"></p>
        </div>

        <div>
            <x-input-label for="name" :value="'Nombre'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$managedUser->name" required />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
        </div>

        <div>
            <x-input-label for="email" :value="'Correo'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="$managedUser->email" />
            <p class="mt-2 text-xs text-stone-500">Opcional.</p>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="email"></p>
        </div>

        @if (auth()->user()->isSuperAdmin())
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
            <x-input-label for="password" :value="$managedUser->exists ? 'Nueva contraseña' : 'Contraseña'" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $managedUser->exists" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="password"></p>
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="$managedUser->exists ? 'Confirmar nueva contraseña' : 'Confirmar contraseña'" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $managedUser->exists" />
        </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            {{ $managedUser->exists ? 'Actualizar usuario' : 'Crear usuario' }}
        </button>
        </div>
    </div>
</form>
