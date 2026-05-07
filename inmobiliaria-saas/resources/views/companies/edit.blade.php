<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Editar empresa" description="Actualiza la información de la empresa y su estado de ciclo de vida." />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('companies.update', $company) }}" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')
                @include('companies._form')
            </form>
        </div>
    </div>
</x-app-layout>
