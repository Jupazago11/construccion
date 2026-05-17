@forelse ($projects as $project)
    @include('projects._row', ['project' => $project])
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron proyectos con los filtros actuales.
        </td>
    </tr>
@endforelse
