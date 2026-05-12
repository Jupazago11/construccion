<div
    x-data="projectStructureState()"
    x-init="restoreSelection($el)"
    x-effect="persistSelection($el)"
    data-project-structure-root
    class="space-y-6"
>
    @if ($project->categories->isNotEmpty())
        <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
            <div
                class="space-y-2"
                data-sortable-list
                data-sortable-url="{{ route('projects.categories.reorder', $project) }}"
            >
                @foreach ($project->categories as $category)
                    <div
                        data-sortable-item
                        data-sortable-id="{{ $category->id }}"
                        x-bind:class="selectedCategoryId !== null && selectedCategoryId !== {{ $category->id }} ? 'opacity-45' : 'opacity-100'"
                        class="group flex items-stretch gap-2 rounded-2xl transition"
                    >
                        @if ($projectAllowsNewRecords)
                            <button
                                type="button"
                                data-sortable-handle
                                class="flex w-11 shrink-0 touch-none cursor-grab items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-400 transition hover:border-stone-300 hover:text-stone-700 active:cursor-grabbing"
                                title="Arrastrar para cambiar orden"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </button>
                        @endif
                        <button
                            type="button"
                            x-on:click="selectedCategoryId = {{ $category->id }}; selectedSubcategoryId = null"
                            x-bind:class="selectedCategoryId === {{ $category->id }}
                                ? 'border-amber-300 bg-amber-100 text-amber-950 shadow-sm ring-2 ring-amber-200'
                                : 'border-stone-200 bg-stone-50 text-stone-700 hover:border-stone-300 hover:bg-stone-100'"
                            class="min-w-0 flex-1 rounded-2xl border px-4 py-4 text-left transition"
                        >
                            <span class="flex items-center justify-between gap-3">
                                <span class="min-w-0 block break-words text-sm font-semibold">{{ $category->name }}</span>
                            </span>
                        </button>
                    </div>
                @endforeach
            </div>
        </section>

        @foreach ($project->categories as $category)
            @php
                $categoryCanBeArchived = ! $category->has_active_expenses
                    && $category->subcategories->every(function ($subcategory) {
                        return ! $subcategory->has_active_expenses
                            && $subcategory->auxiliaries->every(fn ($auxiliary) => ! $auxiliary->has_active_expenses);
                    });
            @endphp
            <section
                x-show="selectedCategoryId === {{ $category->id }}"
                x-cloak
                class="space-y-6"
            >
                <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-semibold text-stone-900">{{ $category->name }}</h3>
                                <button
                                    type="button"
                                    data-action="status"
                                    data-url="{{ route('projects.categories.status', [$project, $category]) }}"
                                    data-current-status="{{ $category->status }}"
                                    data-status-options='@json(["active", "inactive"])'
                                >
                                    <x-status-badge :value="$category->status" class="cursor-pointer transition hover:opacity-80" />
                                </button>
                            </div>

                            @if ($category->description)
                                <p class="mt-2 max-w-3xl text-sm text-stone-600">{{ $category->description }}</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($projectAllowsNewRecords)
                                <button
                                    type="button"
                                    data-action="create"
                                    data-url="{{ route('projects.subcategories.create', $project) }}?category_id={{ $category->id }}"
                                    data-title="Nueva subcategoría - {{ $category->name }}"
                                    class="app-create-text-button"
                                >
                                    Nueva subcategoría
                                </button>
                            @endif

                            <button
                                type="button"
                                data-action="edit"
                                data-url="{{ route('projects.categories.edit', [$project, $category]) }}"
                                data-title="Editar categoría"
                                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                                title="Editar categoría"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                </svg>
                            </button>

                            @if ($categoryCanBeArchived)
                                <button
                                    type="button"
                                    data-action="delete"
                                    data-url="{{ route('projects.categories.destroy', [$project, $category]) }}"
                                    data-confirm-message="¿Deseas archivar esta categoría? Se permite si todavía no tiene gastos registrados; también se archivarán sus subcategorías y auxiliares vacíos."
                                    class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                    title="Archivar categoría"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold text-stone-900">Subcategorías</h4>

                            @if ($category->subcategories->isNotEmpty())
                                <div
                                    class="mt-3 space-y-2"
                                    data-sortable-list
                                    data-sortable-url="{{ route('projects.subcategories.reorder', $project) }}"
                                    data-sortable-parent-name="category_id"
                                    data-sortable-parent-id="{{ $category->id }}"
                                >
                                    @foreach ($category->subcategories as $subcategory)
                                        <div
                                            data-sortable-item
                                            data-sortable-id="{{ $subcategory->id }}"
                                            x-bind:class="selectedSubcategoryId !== null && selectedSubcategoryId !== {{ $subcategory->id }} ? 'opacity-45' : 'opacity-100'"
                                            class="group flex items-stretch gap-2 rounded-2xl transition"
                                        >
                                            @if ($projectAllowsNewRecords)
                                                <button
                                                    type="button"
                                                    data-sortable-handle
                                                    class="flex w-11 shrink-0 touch-none cursor-grab items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-400 transition hover:border-stone-300 hover:text-stone-700 active:cursor-grabbing"
                                                    title="Arrastrar para cambiar orden"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <button
                                                type="button"
                                                x-on:click="selectedSubcategoryId = {{ $subcategory->id }}"
                                                x-bind:class="selectedSubcategoryId === {{ $subcategory->id }}
                                                    ? 'border-sky-300 bg-sky-100 text-sky-950 shadow-sm ring-2 ring-sky-200'
                                                    : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50'"
                                                class="min-w-0 flex-1 rounded-2xl border px-4 py-3 text-left transition"
                                            >
                                                <span class="flex items-start justify-between gap-3">
                                                <span class="min-w-0">
                                                    <span class="block break-words text-sm font-semibold">{{ $subcategory->name }}</span>
                                                    <span class="mt-1 block text-xs opacity-75">{{ $subcategory->auxiliaries->count() }} auxiliares</span>
                                                </span>
                                            </span>
                                        </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($category->subcategories->isNotEmpty())
                    @foreach ($category->subcategories as $subcategory)
                        @php
                            $subcategoryCanBeArchived = ! $subcategory->has_active_expenses
                                && $subcategory->auxiliaries->every(fn ($auxiliary) => ! $auxiliary->has_active_expenses);
                        @endphp
                        <section
                            x-show="selectedSubcategoryId === {{ $subcategory->id }}"
                            x-cloak
                            class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            @php
                                $inactiveAuxiliariesCount = $subcategory->auxiliaries
                                    ->where('status', \App\Enums\EntityStatus::Inactive->value)
                                    ->count();
                                $hasAuxiliaryDescriptions = $subcategory->auxiliaries
                                    ->contains(fn ($auxiliary) => filled($auxiliary->description));
                            @endphp
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-lg font-semibold text-stone-900">{{ $subcategory->name }}</h4>
                                        <button
                                            type="button"
                                            data-action="status"
                                            data-url="{{ route('projects.subcategories.status', [$project, $subcategory]) }}"
                                            data-current-status="{{ $subcategory->status }}"
                                            data-status-options='@json(["active", "inactive"])'
                                        >
                                            <x-status-badge :value="$subcategory->status" class="cursor-pointer transition hover:opacity-80" />
                                        </button>
                                    </div>

                                    @if ($subcategory->description)
                                        <p class="mt-2 max-w-3xl text-sm text-stone-600">{{ $subcategory->description }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($projectAllowsNewRecords)
                                        <button
                                            type="button"
                                            data-action="create"
                                            data-url="{{ route('projects.auxiliaries.create', $project) }}?subcategory_id={{ $subcategory->id }}"
                                            data-title="Nuevo auxiliar - {{ $subcategory->name }}"
                                            class="app-create-text-button"
                                        >
                                            Nuevo auxiliar
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        data-action="edit"
                                        data-url="{{ route('projects.subcategories.edit', [$project, $subcategory]) }}"
                                        data-title="Editar subcategoría"
                                        class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                                        title="Editar subcategoría"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                        </svg>
                                    </button>

                                    @if ($subcategoryCanBeArchived)
                                        <button
                                            type="button"
                                            data-action="delete"
                                            data-url="{{ route('projects.subcategories.destroy', [$project, $subcategory]) }}"
                                            data-confirm-message="¿Deseas archivar esta subcategoría? Se permite si todavía no tiene gastos registrados; también se archivarán sus auxiliares vacíos."
                                            class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                            title="Archivar subcategoría"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div
                                x-data="{ showInactiveAuxiliaries: false }"
                                class="mt-6 overflow-hidden rounded-3xl border border-stone-200"
                            >
                                @if ($inactiveAuxiliariesCount > 0)
                                    <div class="flex items-center justify-between gap-3 border-b border-stone-200 bg-stone-50 px-5 py-3">
                                        <span class="text-sm font-medium text-stone-700">Auxiliares</span>
                                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-stone-600">
                                            <input
                                                type="checkbox"
                                                class="rounded border-stone-300 text-stone-900 shadow-sm focus:ring-stone-900"
                                                x-model="showInactiveAuxiliaries"
                                            >
                                            <span>Mostrar inactivos</span>
                                        </label>
                                    </div>
                                @endif
                                <div class="overflow-x-auto" data-preserve-scroll-key="auxiliaries-{{ $subcategory->id }}">
                                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                                        <thead class="bg-stone-50 text-left text-stone-500">
                                            <tr>
                                                <th class="w-14 px-4 py-4 font-medium"></th>
                                                <th class="min-w-[14rem] px-4 py-4 font-medium sm:px-6">Auxiliar</th>
                                                @if ($hasAuxiliaryDescriptions)
                                                    <th class="min-w-[12rem] px-4 py-4 font-medium sm:px-6">Descripción</th>
                                                @endif
                                                <th class="w-28 px-4 py-4 font-medium">Estado</th>
                                                <th class="w-28 px-4 py-4 font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-stone-100 bg-white"
                                            data-sortable-list
                                            data-sortable-url="{{ route('projects.auxiliaries.reorder', $project) }}"
                                            data-sortable-parent-name="subcategory_id"
                                            data-sortable-parent-id="{{ $subcategory->id }}"
                                        >
                                            @forelse ($subcategory->auxiliaries as $auxiliary)
                                                <tr
                                                    data-sortable-item
                                                    data-sortable-id="{{ $auxiliary->id }}"
                                                    @if ($auxiliary->status === \App\Enums\EntityStatus::Inactive->value)
                                                        x-show="showInactiveAuxiliaries"
                                                        x-cloak
                                                    @endif
                                                >
                                                    <td class="px-4 py-4">
                                                        @if ($projectAllowsNewRecords)
                                                            <button
                                                                type="button"
                                                                data-sortable-handle
                                                                class="flex h-9 w-9 touch-none cursor-grab items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-400 transition hover:border-stone-300 hover:text-stone-700 active:cursor-grabbing"
                                                                title="Arrastrar para cambiar orden"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM7 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 10a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM16 16a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </td>
                                                    <td class="min-w-[14rem] px-4 py-4 sm:px-6">
                                                        <div class="app-two-line-text font-semibold leading-snug text-stone-900" title="{{ $auxiliary->name }}">{{ $auxiliary->name }}</div>
                                                    </td>
                                                    @if ($hasAuxiliaryDescriptions)
                                                        <td class="min-w-[12rem] px-4 py-4 text-stone-600 sm:px-6">
                                                            <div class="app-two-line-text leading-snug">{{ $auxiliary->description ?: '' }}</div>
                                                        </td>
                                                    @endif
                                                    <td class="w-28 px-4 py-4">
                                                        <button
                                                            type="button"
                                                            data-action="status"
                                                            data-url="{{ route('projects.auxiliaries.status', [$project, $auxiliary]) }}"
                                                            data-current-status="{{ $auxiliary->status }}"
                                                            data-status-options='@json(["active", "inactive"])'
                                                        >
                                                            <x-status-badge :value="$auxiliary->status" class="cursor-pointer transition hover:opacity-80" />
                                                        </button>
                                                    </td>
                                                    <td class="w-28 px-4 py-4">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <button
                                                                type="button"
                                                                data-action="edit"
                                                                data-url="{{ route('projects.auxiliaries.edit', [$project, $auxiliary]) }}"
                                                                data-title="Editar auxiliar"
                                                                class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900"
                                                                title="Editar auxiliar"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                                                </svg>
                                                            </button>

                                                            @if (! $auxiliary->has_active_expenses)
                                                                <button
                                                                    type="button"
                                                                    data-action="delete"
                                                                    data-url="{{ route('projects.auxiliaries.destroy', [$project, $auxiliary]) }}"
                                                                    data-confirm-message="¿Deseas archivar este auxiliar? Se permite si todavía no tiene gastos registrados."
                                                                    class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                                                    title="Archivar auxiliar"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-6 py-10 text-center text-stone-500">
                                                        Esta subcategoría todavía no tiene auxiliares.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                    @endforeach
                @endif
            </section>
        @endforeach
    @else
        <div class="rounded-3xl border border-stone-200 bg-white px-6 py-14 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-stone-900">Sin estructura financiera</h2>
            <p class="mt-2 text-sm text-stone-500">Empieza creando la primera categoría de este proyecto.</p>
        </div>
    @endif
</div>
