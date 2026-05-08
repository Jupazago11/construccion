<x-app-layout x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })" x-on:click="handleClick($event)">
    <x-slot name="header">
        <x-page-header :title="'Archivos del gasto '.$expense->expense_number" description="Gestiona soportes, facturas, imágenes y PDFs asociados al gasto.">
            <a href="{{ route('expenses.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Volver a gastos
            </a>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div x-ref="summary">
                @include('expense-attachments._summary', ['expense' => $expense, 'summary' => $summary])
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Cargar archivos</h2>
                <p class="mt-2 text-sm text-stone-500">Se permiten imágenes JPG, PNG, WEBP y archivos PDF de hasta 10 MB por archivo.</p>

                <form method="POST" action="{{ route('expenses.attachments.store', $expense) }}" enctype="multipart/form-data" data-ajax-form class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="files" :value="'Seleccionar archivos'" />
                        <input id="files" name="files[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf" class="mt-1 block w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-700 shadow-sm focus:border-stone-900 focus:outline-none focus:ring-stone-900" />
                        <p class="mt-2 hidden text-sm text-rose-600" data-error-for="files"></p>
                        <p class="mt-2 hidden text-sm text-rose-600" data-error-for="files.0"></p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="rounded-2xl bg-stone-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-stone-700">
                            Subir archivos
                        </button>
                    </div>
                </form>
            </div>

            <div x-ref="attachments">
                @include('expense-attachments._list', ['expense' => $expense])
            </div>

            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
