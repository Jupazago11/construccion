@props(['title', 'description' => null])

<div class="space-y-2">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">{{ $title }}</h1>
        @if (trim($slot))
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>

    <div>
        @if ($description)
            <p class="max-w-3xl text-sm text-stone-500">{{ $description }}</p>
        @endif
    </div>
</div>
