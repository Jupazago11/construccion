<x-app-layout
    x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })"
    x-on:click="handleClick($event)"
    x-on:submit.prevent="submitForm($event)"
>
    <x-slot name="header">
        <x-page-header :title="'Fotos y videos de '.$asset2->name" description="">
            <a href="{{ route('assets2.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Volver a activos
            </a>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @can('update', $asset2)
                <form method="POST" action="{{ route('assets2.media.store', $asset2) }}" enctype="multipart/form-data" data-ajax-form class="sr-only">
                    @csrf

                    <input
                        x-ref="asset2MediaFiles"
                        id="asset2-media-files"
                        name="files[]"
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        x-on:change="$event.target.files.length && $event.target.form.requestSubmit()"
                    />
                    <p data-error-for="files"></p>
                    <p data-error-for="files.0"></p>
                </form>

                <form method="POST" action="{{ route('assets2.media.store', $asset2) }}" enctype="multipart/form-data" data-ajax-form class="sr-only">
                    @csrf

                    <input
                        id="asset2-media-camera"
                        name="files[]"
                        type="file"
                        accept="image/*,video/*"
                        capture="environment"
                        x-on:change="$event.target.files.length && $event.target.form.requestSubmit()"
                    />
                    <p data-error-for="files"></p>
                    <p data-error-for="files.0"></p>
                </form>

                <div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-3 sm:bottom-8 sm:right-8">
                    <label
                        for="asset2-media-camera"
                        class="inline-flex cursor-pointer items-center rounded-full border border-orange-200 bg-white px-4 py-2 text-sm font-medium text-orange-600 shadow-lg transition hover:bg-orange-50"
                        title="Usar camara"
                        :class="saving ? 'pointer-events-none opacity-60' : ''"
                    >
                        Camara
                    </label>
                    <label
                        for="asset2-media-files"
                        class="app-create-button cursor-pointer"
                        title="Subir desde galeria"
                        :class="saving ? 'pointer-events-none opacity-60' : ''"
                    >
                        +
                    </label>
                </div>
            @endcan

            <div x-ref="attachments">
                @include('asset2-media._list', ['asset2' => $asset2])
            </div>

            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
