@props(['title', 'description' => null])

<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">{{ $title }}</h1>
        @if ($description)
            <p class="max-w-3xl text-sm text-stone-500">{{ $description }}</p>
        @endif
    </div>

    @if (trim($slot))
        <div class="flex flex-wrap items-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
