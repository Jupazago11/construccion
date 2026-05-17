@forelse ($companies as $company)
    @include('companies._row', ['company' => $company])
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron empresas con los filtros actuales.
        </td>
    </tr>
@endforelse
