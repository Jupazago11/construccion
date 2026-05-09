@php($firstCategoryId = $project->categories->first()?->id)

<div
    x-data="{ selectedCategoryId: {{ \Illuminate\Support\Js::from($firstCategoryId) }} }"
    class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm"
>
    @if ($project->categories->isNotEmpty())
        <div class="grid min-h-[28rem] lg:grid-cols-[18rem_1fr]">
            <aside class="border-b border-stone-200 bg-stone-50/70 p-3 lg:border-b-0 lg:border-r">
                <div class="flex gap-2 overflow-x-auto pb-1 lg:block lg:space-y-2 lg:overflow-visible lg:pb-0">
                    @foreach ($project->categories as $category)
                        <button
                            type="button"
                            x-on:click="selectedCategoryId = {{ $category->id }}"
                            x-bind:class="selectedCategoryId === {{ $category->id }}
                                ? 'border-stone-900 bg-stone-900 text-white'
                                : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300'"
                            class="min-w-[13rem] rounded-xl border px-4 py-3 text-left transition lg:w-full lg:min-w-0"
                        >
                            <span class="block text-sm font-semibold">{{ $category->name }}</span>
                            <span class="mt-1 block text-xs opacity-75">
                                {{ $category->subcategories->count() }} subcategorias
                            </span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <section class="min-w-0 p-4 sm:p-5">
                @foreach ($project->categories as $category)
                    <div x-show="selectedCategoryId === {{ $category->id }}" x-cloak class="space-y-5">
                        <header class="flex flex-col gap-3 border-b border-stone-200 pb-4 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-stone-950">{{ $category->name }}</h2>
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
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($projectAllowsNewRecords)
                                    <button
                                        type="button"
                                        data-action="create"
                                        data-url="{{ route('projects.subcategories.create', $project) }}?category_id={{ $category->id }}"
                                        data-title="Nueva subcategoria - {{ $category->name }}"
                                        class="rounded-xl bg-stone-900 px-3 py-2 text-xs font-medium text-white transition hover:bg-stone-700"
                                    >
                                        Nueva subcategoria
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    data-action="edit"
                                    data-url="{{ route('projects.categories.edit', [$project, $category]) }}"
                                    data-title="Editar categoria"
                                    class="rounded-xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-50 hover:text-stone-900"
                                    title="Editar categoria"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    data-action="delete"
                                    data-url="{{ route('projects.categories.destroy', [$project, $category]) }}"
                                    data-confirm-message="Deseas archivar esta categoria? Solo se permite si no tiene subcategorias ni gastos."
                                    class="rounded-xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                    title="Archivar categoria"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </header>

                        <div class="space-y-3">
                            @forelse ($category->subcategories as $subcategory)
                                <article class="rounded-xl border border-stone-200 bg-stone-50/60 p-4">
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div class="min-w-0 space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="font-medium text-stone-950">{{ $subcategory->name }}</h3>
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
                                        </div>

                                        <div class="flex shrink-0 flex-wrap gap-2">
                                            @if ($projectAllowsNewRecords)
                                                <button
                                                    type="button"
                                                    data-action="create"
                                                    data-url="{{ route('projects.auxiliaries.create', $project) }}?subcategory_id={{ $subcategory->id }}"
                                                    data-title="Nuevo auxiliar"
                                                    class="rounded-xl border border-stone-300 px-3 py-2 text-xs font-medium text-stone-700 transition hover:bg-white"
                                                >
                                                    Nuevo auxiliar
                                                </button>
                                            @endif

                                            <button
                                                type="button"
                                                data-action="edit"
                                                data-url="{{ route('projects.subcategories.edit', [$project, $subcategory]) }}"
                                                data-title="Editar subcategoria"
                                                class="rounded-xl border border-stone-200 p-2 text-stone-600 transition hover:bg-white hover:text-stone-900"
                                                title="Editar subcategoria"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                data-action="delete"
                                                data-url="{{ route('projects.subcategories.destroy', [$project, $subcategory]) }}"
                                                data-confirm-message="Deseas archivar esta subcategoria? Solo se permite si no tiene auxiliares ni gastos."
                                                class="rounded-xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                                                title="Archivar subcategoria"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @forelse ($subcategory->auxiliaries as $auxiliary)
                                            <span class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-700">
                                                {{ $auxiliary->name }}
                                                <button
                                                    type="button"
                                                    data-action="edit"
                                                    data-url="{{ route('projects.auxiliaries.edit', [$project, $auxiliary]) }}"
                                                    data-title="Editar auxiliar"
                                                    class="text-stone-400 transition hover:text-stone-900"
                                                    title="Editar auxiliar"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    data-action="delete"
                                                    data-url="{{ route('projects.auxiliaries.destroy', [$project, $auxiliary]) }}"
                                                    data-confirm-message="Deseas archivar este auxiliar? Solo se permite si no tiene gastos."
                                                    class="text-rose-400 transition hover:text-rose-700"
                                                    title="Archivar auxiliar"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </span>
                                        @empty
                                            <span class="text-sm text-stone-500">Sin auxiliares.</span>
                                        @endforelse
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center text-sm text-stone-500">
                                    Esta categoria todavia no tiene subcategorias.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </section>
        </div>
    @else
        <div class="px-6 py-14 text-center">
            <h2 class="text-lg font-semibold text-stone-900">Sin estructura financiera</h2>
            <p class="mt-2 text-sm text-stone-500">Empieza creando la primera categoria de este proyecto.</p>
        </div>
    @endif
</div>
