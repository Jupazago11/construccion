@php
    $isEditing = $company->exists;
@endphp

<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" :value="'Nombre de la empresa'" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $company->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="legal_name" :value="'Razón social'" />
        <x-text-input id="legal_name" name="legal_name" type="text" class="mt-1 block w-full" :value="old('legal_name', $company->legal_name)" />
        <x-input-error class="mt-2" :messages="$errors->get('legal_name')" />
    </div>

    <div>
        <x-input-label for="nit" :value="'NIT'" />
        <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" :value="old('nit', $company->nit)" />
        <x-input-error class="mt-2" :messages="$errors->get('nit')" />
    </div>

    <div>
        <x-input-label for="status" :value="'Estado'" />
        <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
            @foreach (['active', 'inactive', 'deleted'] as $status)
                <option value="{{ $status }}" @selected(old('status', $company->status ?: 'active') === $status)>
                    {{ ['active' => 'Activo', 'inactive' => 'Inactivo', 'deleted' => 'Eliminado'][$status] }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="email" :value="'Correo'" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $company->email)" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />
    </div>

    <div>
        <x-input-label for="phone" :value="'Teléfono'" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $company->phone)" />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
    </div>

    <div>
        <x-input-label for="primary_color" :value="'Color principal'" />
        <x-text-input id="primary_color" name="primary_color" type="text" class="mt-1 block w-full" :value="old('primary_color', $company->primary_color)" placeholder="#1f2937" />
        <x-input-error class="mt-2" :messages="$errors->get('primary_color')" />
    </div>

    <div>
        <x-input-label for="logo_path" :value="'Ruta del logo'" />
        <x-text-input id="logo_path" name="logo_path" type="text" class="mt-1 block w-full" :value="old('logo_path', $company->logo_path)" placeholder="companies/logos/example.png" />
        <x-input-error class="mt-2" :messages="$errors->get('logo_path')" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ $isEditing ? route('companies.show', $company) : route('companies.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
        Cancelar
    </a>
    <x-primary-button>
        {{ $isEditing ? 'Actualizar empresa' : 'Crear empresa' }}
    </x-primary-button>
</div>
