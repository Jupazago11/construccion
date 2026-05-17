@forelse ($providers as $provider)
    @include('providers._row', ['provider' => $provider])
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron proveedores con los filtros actuales.
        </td>
    </tr>
@endforelse
