<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$managedUser->name" description="Detalle de la cuenta, empresa asignada y rol actual de autorización.">
            @can('update', $managedUser)
                <a href="{{ route('users.edit', $managedUser) }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                    Editar usuario
                </a>
            @endcan
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Usuario</p>
                        <p class="mt-1 text-stone-700">{{ '@'.$managedUser->username }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Nombre</p>
                        <p class="mt-1 text-lg font-semibold text-stone-900">{{ $managedUser->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Estado</p>
                        <div class="mt-1"><x-status-badge :value="$managedUser->status" /></div>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Correo</p>
                        <p class="mt-1 text-stone-700">{{ $managedUser->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Empresa</p>
                        <p class="mt-1 text-stone-700">{{ $managedUser->company?->name ?: 'Alcance SuperAdmin' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Rol</p>
                        <p class="mt-1 text-stone-700">{{ $managedUser->roles->pluck('name')->join(', ') ?: 'Sin rol asignado' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-stone-400">Creado</p>
                        <p class="mt-1 text-stone-700">{{ $managedUser->created_at?->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>

            @can('delete', $managedUser)
                <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-rose-800">Archivar usuario</h2>
                    <p class="mt-2 text-sm text-rose-700">
                        Esto cambia el estado del usuario a <code>deleted</code>. No elimina físicamente el registro.
                    </p>
                    <form method="POST" action="{{ route('users.destroy', $managedUser) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-2xl bg-rose-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-600">
                            Archivar usuario
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
