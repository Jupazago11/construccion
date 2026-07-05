<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Usuario -->
        <div>
            <x-input-label for="username" :value="'Usuario'" />
            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4" x-data="{ showPassword: false }">
            <x-input-label for="password" :value="'Contraseña'" />

            <div class="relative mt-1">
                <x-text-input
                    id="password"
                    class="block w-full pr-10"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                />
                <button
                    type="button"
                    tabindex="-1"
                    x-on:click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 transition hover:text-stone-700"
                >
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg x-show="showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                        <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.064 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                Ingresar
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 border-t border-stone-200 pt-6">
        <p class="text-center text-xs font-semibold uppercase tracking-[0.14em] text-stone-400">Vehículo</p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('vehiculo.index') }}" class="flex-1 rounded-2xl border border-stone-300 px-4 py-2 text-center text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Registrar ingreso/gasto
            </a>
            <a href="{{ route('vehiculo.dashboard') }}" class="flex-1 rounded-2xl border border-stone-300 px-4 py-2 text-center text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Ver indicadores
            </a>
        </div>
    </div>
</x-guest-layout>
