<div class="space-y-5">
    @forelse ($project->categories as $category)
        <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-stone-200 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-lg font-semibold text-stone-900">{{ $category->name }}</h2>
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
                        <p class="max-w-3xl text-sm text-stone-600">{{ $category->description }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2 text-xs text-stone-500">
                        <span class="rounded-full bg-stone-100 px-3 py-1">Orden: {{ $category->sort_order }}</span>
                        <span class="rounded-full bg-stone-100 px-3 py-1">Subcategorías: {{ $category->subcategories->count() }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($projectAllowsNewRecords)
                        <button
                            type="button"
                            data-action="create"
                            data-url="{{ route('projects.subcategories.create', $project) }}?category_id={{ $category->id }}"
                            data-title="Nueva subcategoría"
                            class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
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
                        data-confirm-message="¿Deseas archivar esta categoría? Solo se permite si no tiene subcategorías ni gastos."
                        class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                        title="Archivar categoría"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-4 px-6 py-5">
                @forelse ($category->subcategories as $subcategory)
                    <article class="rounded-3xl border border-stone-200 bg-stone-50/70 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-base font-semibold text-stone-900">{{ $subcategory->name }}</h3>
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
                                    <p class="text-sm text-stone-600">{{ $subcategory->description }}</p>
                                @endif

                                <div class="flex flex-wrap gap-2 text-xs text-stone-500">
                                    <span class="rounded-full bg-white px-3 py-1">Orden: {{ $subcategory->sort_order }}</span>
                                    <span class="rounded-full bg-white px-3 py-1">Auxiliares: {{ $subcategory->auxiliaries->count() }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($projectAllowsNewRecords)
                                    <button
                                        type="button"
                                        data-action="create"
                                        data-url="{{ route('projects.auxiliaries.create', $project) }}?subcategory_id={{ $subcategory->id }}"
                                        data-title="Nuevo auxiliar"
                                        class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
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

                                <button
                                    type="button"
                                    data-action="delete"
                                    data-url="{{ route('projects.subcategories.destroy', [$project, $subcategory]) }}"
                                    data-confirm-message="¿Deseas archivar esta subcategoría? Solo se permite si no tiene auxiliares ni gastos."
                                    class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                    title="Archivar subcategoría"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            @forelse ($subcategory->auxiliaries as $auxiliary)
                                <div class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-4 md:flex-row md:items-start md:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h4 class="font-semibold text-stone-900">{{ $auxiliary->name }}</h4>
                                            <button
                                                type="button"
                                                data-action="status"
                                                data-url="{{ route('projects.auxiliaries.status', [$project, $auxiliary]) }}"
                                                data-current-status="{{ $auxiliary->status }}"
                                                data-status-options='@json(["active", "inactive"])'
                                            >
                                                <x-status-badge :value="$auxiliary->status" class="cursor-pointer transition hover:opacity-80" />
                                            </button>
                                        </div>

                                        @if ($auxiliary->description)
                                            <p class="text-sm text-stone-600">{{ $auxiliary->description }}</p>
                                        @endif

                                        <span class="inline-flex rounded-full bg-stone-100 px-3 py-1 text-xs text-stone-500">Orden: {{ $auxiliary->sort_order }}</span>
                                    </div>

                                    <div class="flex items-center gap-2">
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
                                            data-confirm-message="¿Deseas archivar este auxiliar? Solo se permite si no tiene gastos."
                                            class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                            title="Archivar auxiliar"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-5 text-sm text-stone-500">
                                    Esta subcategoría no tiene auxiliares registrados.
                                </div>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50 px-5 py-8 text-center text-sm text-stone-500">
                        Esta categoría todavía no tiene subcategorías.
                    </div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center">
            <h2 class="text-lg font-semibold text-stone-900">Sin estructura financiera</h2>
            <p class="mt-2 text-sm text-stone-500">Empieza creando la primera categoría de este proyecto.</p>
        </div>
    @endforelse
</div>
