<form
    data-standalone-invoice-form
    data-invoice-type="{{ $type }}"
    data-invoice-store-url="{{ $storeUrl }}"
    class="flex h-full min-h-0 flex-col gap-4 overflow-hidden"
>
    <script type="application/json" data-invoice-projects>{!! json_encode($projects->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'company_id' => $p->company_id])->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    <script type="application/json" data-invoice-providers>{!! json_encode($providers->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'company_id' => $p->company_id])->values(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <div class="min-h-0 flex-1 overflow-y-auto pr-1">
        <div class="grid gap-5 md:grid-cols-2">

            <div class="md:col-span-2">
                <x-input-label for="inv_project_id" :value="'Proyecto'" />
                <select id="inv_project_id" data-invoice-project class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900">
                    <option value="">Selecciona un proyecto</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
                <p class="mt-2 hidden text-sm text-rose-600" data-invoice-error-for="project_id"></p>
            </div>

            <div class="relative md:col-span-2">
                <x-input-label for="inv_provider_search" :value="'Proveedor'" />
                <input id="inv_provider_search" data-invoice-provider-search class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900" autocomplete="off" placeholder="Buscar proveedor...">
                <input type="hidden" data-invoice-provider>
                <div data-invoice-provider-menu class="absolute left-0 right-0 z-[70] mt-2 hidden max-h-52 overflow-y-auto rounded-2xl border border-stone-200 bg-white p-1 shadow-xl"></div>
                <p class="mt-2 hidden text-sm text-rose-600" data-invoice-error-for="provider_id"></p>
            </div>

            <div>
                <x-input-label for="inv_invoice_number" :value="'Referencia de factura (opcional)'" />
                <x-text-input id="inv_invoice_number" data-invoice-number type="text" class="mt-1 block w-full" />
            </div>

            <div>
                <x-input-label for="inv_invoice_date" :value="'Fecha'" />
                <x-text-input id="inv_invoice_date" data-invoice-date type="date" class="mt-1 block w-full" :value="now()->toDateString()" />
            </div>

            <div class="md:col-span-2">
                <x-input-label for="inv_description" :value="'Observación (opcional)'" />
                <textarea id="inv_description" data-invoice-description rows="2" class="mt-1 block w-full rounded-2xl border-stone-300 shadow-sm focus:border-stone-900 focus:ring-stone-900"></textarea>
            </div>

        </div>
    </div>

    <div class="sticky bottom-0 z-10 mt-auto shrink-0 border-t border-stone-200 bg-white px-1 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-4 shadow-[0_-8px_18px_rgba(255,255,255,0.92)]">
        <div class="flex items-center justify-end gap-3">
            <button type="button" data-action="close-modal" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Cancelar
            </button>
            <button type="button" data-invoice-save class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700 disabled:cursor-wait disabled:opacity-60">
                Crear factura
            </button>
        </div>
    </div>
</form>
