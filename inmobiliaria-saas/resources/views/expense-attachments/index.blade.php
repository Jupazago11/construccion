<x-app-layout
    x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })"
    x-on:click="handleClick($event)"
    x-on:submit.prevent="submitForm($event)"
>
    <x-slot name="header">
        <x-page-header :title="'Archivos del gasto '.$expense->expense_number" description="">
            <a href="{{ route('expenses.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Volver a gastos
            </a>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @can('create', App\Models\ExpenseAttachment::class)
                <form method="POST" action="{{ route('expenses.attachments.store', $expense) }}" enctype="multipart/form-data" data-ajax-form class="sr-only">
                    @csrf

                    <input
                        x-ref="expenseAttachmentFiles"
                        id="expense-attachment-files"
                        name="files[]"
                        type="file"
                        multiple
                        accept="image/*,.pdf,application/pdf,video/*"
                        x-on:change="$event.target.files.length && $event.target.form.requestSubmit()"
                    />
                    <p data-error-for="files"></p>
                    <p data-error-for="files.0"></p>
                </form>

                <button
                    type="button"
                    class="app-create-button"
                    title="Subir archivos"
                    x-on:click="$refs.expenseAttachmentFiles.click()"
                    :disabled="saving"
                >
                    +
                </button>
            @endcan

            <div x-ref="attachments">
                @include('expense-attachments._list', ['expense' => $expense])
            </div>

            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
