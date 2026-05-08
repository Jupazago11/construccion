<div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-4">
        <h2 class="text-lg font-semibold text-stone-900">Archivos registrados</h2>
        <p class="text-sm text-stone-500">{{ $expense->attachments->count() }} activos</p>
    </div>

    <div class="mt-5 space-y-3">
        @forelse ($expense->attachments as $attachment)
            <div class="flex flex-col gap-4 rounded-2xl border border-stone-200 px-4 py-4 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    <div class="font-semibold text-stone-900">{{ $attachment->original_name ?: basename($attachment->path) }}</div>
                    <div class="mt-1 text-sm text-stone-500">{{ $attachment->mime_type ?: 'Tipo no disponible' }}</div>
                    <div class="mt-1 text-sm text-stone-500">
                        {{ $attachment->uploader?->name ?: 'Usuario no disponible' }} · {{ number_format(((int) $attachment->size) / 1024, 2) }} KB
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('expenses.attachments.download', [$expense, $attachment]) }}" class="rounded-2xl border border-stone-200 px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                        Descargar
                    </a>
                    <button
                        type="button"
                        data-action="delete"
                        data-url="{{ route('expenses.attachments.destroy', [$expense, $attachment]) }}"
                        data-confirm-message="¿Deseas archivar este archivo?"
                        class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50"
                        title="Archivar archivo"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-4 py-10 text-center text-sm text-stone-500">
                Este gasto todavía no tiene archivos adjuntos activos.
            </div>
        @endforelse
    </div>
</div>
