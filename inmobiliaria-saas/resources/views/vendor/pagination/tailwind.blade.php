@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="space-y-3">
        <div class="overflow-x-auto sm:hidden">
            <div class="inline-flex min-w-full items-center justify-center gap-1 pb-1">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-400">
                        {{ __('pagination.previous') }}
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        {{ __('pagination.previous') }}
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center px-2 py-2 text-sm text-stone-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl bg-stone-900 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl border border-stone-300 px-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        {{ __('pagination.next') }}
                    </a>
                @else
                    <span class="inline-flex items-center rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-400">
                        {{ __('pagination.next') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="hidden sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-stone-600">
                    {{ __('Showing') }}
                    @if ($paginator->firstItem())
                        <span class="font-semibold">{{ $paginator->firstItem() }}</span>
                        {{ __('to') }}
                        <span class="font-semibold">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {{ __('of') }}
                    <span class="font-semibold">{{ $paginator->total() }}</span>
                    {{ __('results') }}
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1">
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex items-center rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-400">
                            {{ __('pagination.previous') }}
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            {{ __('pagination.previous') }}
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="inline-flex items-center px-2 py-2 text-sm text-stone-400">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl bg-stone-900 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-2xl border border-stone-300 px-3 text-sm font-medium text-stone-700 transition hover:bg-stone-50">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center rounded-2xl border border-stone-300 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                            {{ __('pagination.next') }}
                        </a>
                    @else
                        <span class="inline-flex items-center rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-400">
                            {{ __('pagination.next') }}
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
