@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 text-sm" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="text-stone-500">
            Mostrando
            <span class="font-medium text-stone-900">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-stone-900">{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-stone-900">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-stone-200 px-3 text-stone-400" aria-disabled="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.78 15.53a.75.75 0 01-1.06 0l-5-5a.75.75 0 010-1.06l5-5a.75.75 0 111.06 1.06L8.31 10l4.47 4.47a.75.75 0 010 1.06z" clip-rule="evenodd" />
                    </svg>
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-stone-300 px-3 text-stone-700 transition hover:bg-stone-50" aria-label="Página anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.78 15.53a.75.75 0 01-1.06 0l-5-5a.75.75 0 010-1.06l5-5a.75.75 0 111.06 1.06L8.31 10l4.47 4.47a.75.75 0 010 1.06z" clip-rule="evenodd" />
                    </svg>
                    Anterior
                </a>
            @endif

            <span class="text-stone-500">
                Página <span class="font-medium text-stone-900">{{ $paginator->currentPage() }}</span>
                de <span class="font-medium text-stone-900">{{ $paginator->lastPage() }}</span>
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-stone-300 px-3 text-stone-700 transition hover:bg-stone-50" aria-label="Página siguiente">
                    Siguiente
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.22 4.47a.75.75 0 011.06 0l5 5a.75.75 0 010 1.06l-5 5a.75.75 0 11-1.06-1.06L11.69 10 7.22 5.53a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-stone-200 px-3 text-stone-400" aria-disabled="true">
                    Siguiente
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.22 4.47a.75.75 0 011.06 0l5 5a.75.75 0 010 1.06l-5 5a.75.75 0 11-1.06-1.06L11.69 10 7.22 5.53a.75.75 0 010-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
