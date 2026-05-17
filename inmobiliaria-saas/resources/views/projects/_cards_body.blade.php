@forelse ($projects as $project)
    @if (! $loop->first)
        <div class="h-px bg-stone-200/80 md:hidden" aria-hidden="true"></div>
    @endif
    @include('projects._card', ['project' => $project])
@empty
    <div data-empty-state class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center text-stone-500 md:col-span-2 xl:col-span-3">
        No se encontraron proyectos registrados.
    </div>
@endforelse
