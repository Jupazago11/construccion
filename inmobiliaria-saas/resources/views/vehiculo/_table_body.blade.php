@forelse ($records as $record)
    @include('vehiculo._row', ['record' => $record])
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron registros con los filtros actuales.
        </td>
    </tr>
@endforelse
