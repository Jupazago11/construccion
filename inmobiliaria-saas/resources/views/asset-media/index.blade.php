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
                <form method="POST" action="{{ route('assets.media.store', $asset) }}" enctype="multipart/form-data" data-ajax-form class="sr-only">
                    @csrf

                    <input
                        x-ref="assetMediaFiles"
                        id="asset-media-files"
                        name="files[]"
                        type="file"
                        multiple
                        accept="image/*,video/*"
                        x-on:change="$event.target.files.length && $event.target.form.requestSubmit()"
                    />
                    <p data-error-for="files"></p>
                    <p data-error-for="files.0"></p>
                </form>

                <label
                    for="asset-media-files"
                    class="app-create-button cursor-pointer"
                    title="Subir fotos o videos"
                    :class="saving ? 'pointer-events-none opacity-60' : ''"
                >
                    +
                </label>
            @endcan

            <div x-ref="attachments">
                @include('asset-media._list', ['asset' => $asset])
            </div>

            <x-ajax-crud-toast />
        </div>
    </div>
</x-app-layout>
