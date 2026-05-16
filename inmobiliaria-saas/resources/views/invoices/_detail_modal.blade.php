<div
    class="flex h-full min-h-0 flex-col gap-5 overflow-hidden"
    data-invoice-detail-root
    data-invoice-id="{{ $invoice->id }}"
    x-data="{ previewOpen: false, previewUrl: '', previewType: '', previewName: '' }"
>
    <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="font-semibold text-stone-900">Factura {{ $invoice->invoice_number ?: 'sin número' }}</div>
                <div class="mt-1 truncate">{{ $invoice->provider?->name ?: 'Sin proveedor' }} · {{ $invoice->project?->name ?: 'Sin proyecto' }}</div>
            </div>
            <button type="button" data-action="invoice-delete" data-url="{{ route('invoices.destroy', $invoice) }}" class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50" title="Eliminar factura">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
            </button>
        </div>
        <form data-invoice-attachment-form action="{{ route('invoices.attachments.store', $invoice) }}" class="mt-3">
            <input id="invoice_files_{{ $invoice->id }}" name="files[]" type="file" multiple accept="image/*,video/*,application/pdf" class="sr-only" data-invoice-file-input>
            <label for="invoice_files_{{ $invoice->id }}" class="app-create-icon-button h-9 w-9 cursor-pointer rounded-full" title="Subir material audiovisual">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
            </label>
        </form>
    </div>

    <div class="shrink-0 rounded-2xl border border-stone-200 bg-stone-50 p-4">
        <div class="max-h-44 overflow-y-auto pr-1" data-invoice-attachments-root data-invoice-attachments-scroll>
            @include('invoices._attachments', ['invoice' => $invoice])
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto" data-invoice-items-scroll>
        <div class="overflow-x-auto rounded-2xl border border-stone-200" data-invoice-items-x-scroll>
            <table class="min-w-full divide-y divide-stone-200 whitespace-nowrap text-sm">
                <thead class="bg-stone-50 text-left text-stone-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Fecha</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Cantidad</th>
                        <th class="px-4 py-3 font-medium">Descripción</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium">Estado</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($items as $item)
                        @php($isPurchase = $invoice->type === 'purchase')
                        <tr data-row-id="{{ $item->id }}">
                            <td class="px-4 py-3 text-stone-600">{{ ($isPurchase ? $item->purchase_date : $item->expense_date)?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-stone-700">
                                <div class="font-medium text-stone-900">{{ $item->product?->name ?: 'Sin producto' }}</div>
                                <div class="text-xs text-stone-500">{{ $item->product?->subgroup?->name ?: 'Sin subgrupo' }}</div>
                            </td>
                            <td class="px-4 py-3 text-stone-600">
                                @if ($item->quantity && $item->quantity != 1)
                                    {{ number_format((float) $item->quantity, 2, ',', '.') }} × $ {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-stone-600">{{ $item->description ?: 'Sin descripción' }}</td>
                            <td class="px-4 py-3 text-stone-900">$ {{ number_format((float) $item->total_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    data-action="status"
                                    data-url="{{ $isPurchase ? route('purchases.status', $item) : route('expenses.status', $item) }}"
                                    data-current-status="{{ $item->status }}"
                                    data-status-options='@json(["active", "inactive"])'
                                >
                                    <x-status-badge :value="$item->status" class="cursor-pointer transition hover:opacity-80" />
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" data-action="edit" data-url="{{ $isPurchase ? route('purchases.edit', $item) : route('expenses.edit', $item) }}" data-title="{{ $isPurchase ? 'Editar compra' : 'Editar gasto' }}" class="rounded-2xl border border-stone-200 p-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 010 2.828l-8.5 8.5a2 2 0 01-.878.497l-3 1a1 1 0 01-1.265-1.265l1-3a2 2 0 01.497-.878l8.5-8.5a2 2 0 012.828 0z" /></svg>
                                    </button>
                                    <button type="button" data-action="delete" data-url="{{ $isPurchase ? route('purchases.destroy', $item) : route('expenses.destroy', $item) }}" data-confirm-message="{{ $isPurchase ? '¿Deseas archivar esta compra?' : '¿Deseas archivar este gasto? Solo se permite si no tiene archivos adjuntos.' }}" class="rounded-2xl border border-rose-200 p-2 text-rose-700 transition hover:bg-rose-50" title="Archivar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.366-.446.911-.699 1.486-.699h.514c.575 0 1.12.253 1.486.699L12.85 4H16a1 1 0 110 2h-1l-.867 10.142A2 2 0 0112.14 18H7.86a2 2 0 01-1.993-1.858L5 6H4a1 1 0 010-2h3.15l1.107-.901zM8 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">Sin {{ $typeLabel }} registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="shrink-0 border-t border-stone-200 pt-4">
        <div class="flex items-center justify-end">
            <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Cerrar
            </button>
        </div>
    </div>

    <div
        x-show="previewOpen"
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center bg-black/70 p-4"
        x-on:keydown.escape.window="previewOpen = false"
    >
        <div class="max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between gap-3 border-b border-stone-200 px-5 py-4">
                <h3 class="truncate text-sm font-semibold text-stone-900" x-text="previewName"></h3>
                <button type="button" class="rounded-2xl border border-stone-200 px-3 py-2 text-sm text-stone-700 transition hover:bg-stone-50" x-on:click="previewOpen = false">
                    Cerrar
                </button>
            </div>

            <div class="flex max-h-[78vh] items-center justify-center bg-stone-950 p-3">
                <template x-if="previewType === 'image'">
                    <img :src="previewUrl" :alt="previewName" class="max-h-[74vh] max-w-full rounded-xl object-contain">
                </template>

                <template x-if="previewType === 'video'">
                    <video :src="previewUrl" class="max-h-[74vh] max-w-full rounded-xl" controls playsinline></video>
                </template>
            </div>
        </div>
    </div>
</div>
