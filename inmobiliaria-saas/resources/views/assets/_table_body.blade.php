@forelse ($assets as $asset)
    @include('assets._row', ['asset' => $asset])
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron activos con los filtros actuales.
        </td>
    </tr>
@endforelse
