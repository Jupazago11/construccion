@php
    $isEditing = $project->exists;
    $selectedCompany = old('company_id', $project->company_id ?: request('company_id'));
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
        <x-input-label for="name" :value="'Nombre del proyecto'" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name)" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="project_type" :value="'Tipo de proyecto'" />
        <x-text-input id="project_type" name="project_type" type="text" class="mt-1 block w-full" :value="old('project_type', $project->project_type)" placeholder="apartments, houses, lots, mixed, other" />
        <x-input-error class="mt-2" :messages="$errors->get('project_type')" />
    </div>

    <div>
        <x-input-label for="status" :value="'Estado'" />
        <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
            @foreach (['planning', 'active', 'paused', 'completed', 'cancelled', 'deleted'] as $status)
                <option value="{{ $status }}" @selected(old('status', $project->status ?: 'planning') === $status)>
                    {{ [
                        'planning' => 'Planeación',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        'deleted' => 'Eliminado',
                    ][$status] }}
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('status')" />
    </div>

    <div>
        <x-input-label for="country" :value="'País'" />
        <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $project->country ?: 'Colombia')" required />
        <x-input-error class="mt-2" :messages="$errors->get('country')" />
    </div>

    <div>
        <x-input-label for="state" :value="'Departamento / Estado'" />
        <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="old('state', $project->state)" />
        <x-input-error class="mt-2" :messages="$errors->get('state')" />
    </div>

    <div>
        <x-input-label for="city" :value="'Ciudad'" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $project->city)" />
        <x-input-error class="mt-2" :messages="$errors->get('city')" />
    </div>

    <div>
        <x-input-label for="start_date" :value="'Fecha de inicio'" />
        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', optional($project->start_date)->format('Y-m-d'))" />
        <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="address" :value="'Dirección'" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $project->address)" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="location_reference" :value="'Referencia de ubicación'" />
        <x-text-input id="location_reference" name="location_reference" type="text" class="mt-1 block w-full" :value="old('location_reference', $project->location_reference)" />
        <x-input-error class="mt-2" :messages="$errors->get('location_reference')" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="description" :value="'Descripción'" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ old('description', $project->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ $isEditing ? route('projects.show', $project) : route('projects.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
        Cancelar
    </a>
    <x-primary-button>
        {{ $isEditing ? 'Actualizar proyecto' : 'Crear proyecto' }}
    </x-primary-button>
</div>
