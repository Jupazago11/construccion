<x-app-layout
    x-data="crudTable({ flash: {{ \Illuminate\Support\Js::from(session('status')) }} })"
    x-on:click="handleClick($event)"
    x-on:submit.prevent="submitForm($event)"
>
    <x-slot name="header">
        <x-page-header :title="'Fotos y videos de '.$asset->name" description="">
            <a href="{{ route('assets.index') }}" class="rounded-2xl border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50">
                Volver a activos
            </a>
        </x-page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @can('update', $asset)
                <form method="POST" action="{{ route('assets.media.store', $asset) }}" enctype="multipart/form-data" data-ajax-form>
                    @csrf

                    <input
                        id="asset-media-files"
                        name="files[]"
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        class="sr-only"
                        x-on:change="$event.target.files.length && $event.target.form.requestSubmit()"
                    />
                    <p data-error-for="files"></p>
                    <p data-error-for="files.0"></p>
                    <label
                        for="asset-media-files"
                        class="app-create-icon-button h-11 w-11 cursor-pointer rounded-full"
                        title="Subir foto o video"
                        :class="saving ? 'pointer-events-none opacity-60' : ''"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" /></svg>
                    </label>
                </form>
            @endcan

            <div x-ref="attachments">
                @include('asset-media._list', ['asset' => $asset])
            </div>

            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
