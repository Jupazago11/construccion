@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();

    $pages = collect([1, $currentPage - 1, $currentPage, $currentPage + 1, $lastPage])
        ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
        ->unique()
        ->sort()
        ->values();
@endphp

<nav class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" aria-label="Paginacion">
    <p class="text-sm text-stone-500">
        Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}
    </p>

    <div class="flex flex-wrap items-center justify-end gap-2">
        @if ($paginator->onFirstPage() === false)
            <a
                href="{{ $paginator->previousPageUrl() }}"
                class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                x-on:click.prevent="goToPage(@js($paginator->previousPageUrl()))"
            >Anterior</a>
        @endif

        @if ($currentPage > 2)
            <a
                href="{{ $paginator->url(1) }}"
                class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                x-on:click.prevent="goToPage(@js($paginator->url(1)))"
            >1</a>
        @endif

        @if ($pages->isNotEmpty() && $pages->first() > 2)
            <span class="px-1 text-sm text-stone-400">...</span>
        @endif

        @foreach ($pages as $page)
            @continue($page === 1 && $currentPage > 2)
            @continue($page === $lastPage && $currentPage < ($lastPage - 1))

            @if ($page === $currentPage)
                <span class="rounded-2xl bg-stone-900 px-3 py-2 text-sm font-semibold text-white">{{ $page }}</span>
            @else
                <a
                    href="{{ $paginator->url($page) }}"
                    class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                    x-on:click.prevent="goToPage(@js($paginator->url($page)))"
                >{{ $page }}</a>
            @endif
        @endforeach

        @if ($pages->isNotEmpty() && $pages->last() < ($lastPage - 1))
            <span class="px-1 text-sm text-stone-400">...</span>
        @endif

        @if ($currentPage < ($lastPage - 1))
            <a
                href="{{ $paginator->url($lastPage) }}"
                class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                x-on:click.prevent="goToPage(@js($paginator->url($lastPage)))"
            >{{ $lastPage }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a
                href="{{ $paginator->nextPageUrl() }}"
                class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                x-on:click.prevent="goToPage(@js($paginator->nextPageUrl()))"
            >Siguiente</a>
        @endif

        @if ($currentPage < ($lastPage - 1))
            <a
                href="{{ $paginator->url($lastPage) }}"
                class="rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
                x-on:click.prevent="goToPage(@js($paginator->url($lastPage)))"
            >Ir a la ultima</a>
        @endif
    </div>
</nav>
