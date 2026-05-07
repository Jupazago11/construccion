<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Crear empresa" description="Registra una nueva empresa dentro de la plataforma SaaS." />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('companies.store') }}" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                @csrf
                @include('companies._form')
            </form>
        </div>
    </div>
</x-app-layout>
