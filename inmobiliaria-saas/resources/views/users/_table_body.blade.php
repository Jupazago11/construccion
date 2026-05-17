@forelse ($users as $managedUser)
    @include('users._row', ['user' => $managedUser])
@empty
    <tr data-empty-state>
        <td colspan="5" class="px-6 py-10 text-center text-stone-500">
            No se encontraron usuarios con los filtros actuales.
        </td>
    </tr>
@endforelse
