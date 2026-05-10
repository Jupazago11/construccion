@php
    $statusOptions = [
        'planning' => 'En gestión',
        'active' => 'Activo',
        'paused' => 'Pausado',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
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
                        <option value="{{ $company->id }}" @selected(($project->company_id ?: request('company_id')) == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="company_id"></p>
            </div>
        @endif

        <div>
            <x-input-label for="name" :value="'Nombre del proyecto'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$project->name" required />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="name"></p>
        </div>

        <div>
            <x-input-label for="project_type" :value="'Tipo de proyecto'" />
            <x-text-input id="project_type" name="project_type" type="text" class="mt-1 block w-full" :value="$project->project_type" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="project_type"></p>
        </div>

        @unless($project->exists)
            <div>
                <x-input-label for="status" :value="'Estado'" />
                <select id="status" name="status" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($project->status ?: 'planning') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-error-for="status"></p>
            </div>
        @endunless

        <div>
            <x-input-label for="country" :value="'País'" />
            <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="$project->country ?: 'Colombia'" required />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="country"></p>
        </div>

        <div>
            <x-input-label for="state" :value="'Departamento / Estado'" />
            <x-text-input id="state" name="state" type="text" class="mt-1 block w-full" :value="$project->state" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="state"></p>
        </div>

        <div>
            <x-input-label for="city" :value="'Ciudad'" />
            <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="$project->city" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="city"></p>
        </div>

        <div>
            <x-input-label for="start_date" :value="'Fecha de inicio'" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="optional($project->start_date)->format('Y-m-d')" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="start_date"></p>
        </div>

        <div class="md:col-span-2">
            <x-input-label for="address" :value="'Dirección'" />
            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="$project->address" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="address"></p>
        </div>

        <div class="md:col-span-2">
            <x-input-label for="location_reference" :value="'Referencia de ubicación'" />
            <x-text-input id="location_reference" name="location_reference" type="text" class="mt-1 block w-full" :value="$project->location_reference" />
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="location_reference"></p>
        </div>

        <div class="md:col-span-2">
            <x-input-label for="description" :value="'Descripción'" />
            <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">{{ $project->description }}</textarea>
            <p class="mt-2 hidden text-sm text-rose-600" data-error-for="description"></p>
        </div>
        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
        <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
            Cancelar
        </button>
        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
            {{ $project->exists ? 'Actualizar proyecto' : 'Crear proyecto' }}
        </button>
        </div>
    </div>
</form>
