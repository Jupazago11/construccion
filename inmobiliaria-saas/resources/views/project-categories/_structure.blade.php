<div
    x-data="{
        selectedCategoryId: null,
        selectedSubcategoryId: null,
    }"
    class="space-y-6"
>
    @if ($project->categories->isNotEmpty())
        <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($project->categories as $category)
                    <button
                        type="button"
                        x-on:click="selectedCategoryId = {{ $category->id }}; selectedSubcategoryId = null"
                        x-bind:class="selectedCategoryId === {{ $category->id }}
                            ? 'border-amber-300 bg-amber-100 text-amber-950 shadow-sm ring-2 ring-amber-200'
                            : 'border-stone-200 bg-stone-50 text-stone-700 hover:border-stone-300 hover:bg-stone-100'"
                        class="rounded-2xl border px-4 py-4 text-left transition"
                    >
                        <span class="flex items-center justify-between gap-3">
                            <span class="min-w-0 block break-words text-sm font-semibold">{{ $category->name }}</span>
                            <span class="shrink-0"><x-status-badge :value="$category->status" /></span>
                        </span>
                    </button>
                @endforeach
            </div>
        </section>

        @foreach ($project->categories as $category)
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
                            @else
                                <p class="mt-2 max-w-3xl text-sm text-stone-500">Usa esta categoría como contenedor principal de subcategorías y auxiliares del proyecto.</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($projectAllowsNewRecords)
                                <button
                                    type="button"
                                    data-action="create"
                                    data-url="{{ route('projects.subcategories.create', $project) }}?category_id={{ $category->id }}"
                                    data-title="Nueva subcategoría - {{ $category->name }}"
                                    class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700"
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

                            <button
                                type="button"
                                data-action="delete"
                                data-url="{{ route('projects.categories.destroy', [$project, $category]) }}"
                                data-confirm-message="Deseas archivar esta categoría? Solo se permite si no tiene subcategorías ni gastos."
                                class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                title="Archivar categoría"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold text-stone-900">Subcategorías</h4>

                            @if ($category->subcategories->isNotEmpty())
                                <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($category->subcategories as $subcategory)
                                        <button
                                            type="button"
                                            x-on:click="selectedSubcategoryId = {{ $subcategory->id }}"
                                            x-bind:class="selectedSubcategoryId === {{ $subcategory->id }}
                                                ? 'border-sky-300 bg-sky-100 text-sky-950 shadow-sm ring-2 ring-sky-200'
                                                : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50'"
                                            class="rounded-2xl border px-4 py-3 text-left transition"
                                        >
                                            <span class="flex items-start justify-between gap-3">
                                                <span class="min-w-0">
                                                    <span class="block break-words text-sm font-semibold">{{ $subcategory->name }}</span>
                                                    <span class="mt-1 block text-xs opacity-75">{{ $subcategory->auxiliaries->count() }} auxiliares</span>
                                                </span>
                                                <span class="shrink-0">
                                                    <x-status-badge :value="$subcategory->status" />
                                                </span>
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($category->subcategories->isNotEmpty())
                    @foreach ($category->subcategories as $subcategory)
                        <section
                            x-show="selectedSubcategoryId === {{ $subcategory->id }}"
                            x-cloak
                            class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6"
                        >
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
                                    @else
                                        <p class="mt-2 max-w-3xl text-sm text-stone-500">Selecciona o crea auxiliares para esta subcategoría.</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($projectAllowsNewRecords)
                                        <button
                                            type="button"
                                            data-action="create"
                                            data-url="{{ route('projects.auxiliaries.create', $project) }}?subcategory_id={{ $subcategory->id }}"
                                            data-title="Nuevo auxiliar - {{ $subcategory->name }}"
                                            class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700"
                                        >
                                            Nuevo auxiliar
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-6 overflow-hidden rounded-3xl border border-stone-200">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                                        <thead class="bg-stone-50 text-left text-stone-500">
                                            <tr>
                                                <th class="px-6 py-4 font-medium">Auxiliar</th>
                                                <th class="px-6 py-4 font-medium">Descripción</th>
                                                <th class="px-6 py-4 font-medium">Estado</th>
                                                <th class="px-6 py-4 font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-stone-100 bg-white">
                                            @forelse ($subcategory->auxiliaries as $auxiliary)
                                                <tr>
                                                    <td class="px-6 py-4">
                                                        <div class="font-semibold text-stone-900">{{ $auxiliary->name }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 text-stone-600">
                                                        {{ $auxiliary->description ?: 'Sin descripción' }}
                                                    </td>
                                                    <td class="px-6 py-4">
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
                                                    <td class="px-6 py-4">
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

                                                            <button
                                                                type="button"
                                                                data-action="delete"
                                                                data-url="{{ route('projects.auxiliaries.destroy', [$project, $auxiliary]) }}"
                                                                data-confirm-message="Deseas archivar este auxiliar? Solo se permite si no tiene gastos."
                                                                class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                                                title="Archivar auxiliar"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-6 py-10 text-center text-stone-500">
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
