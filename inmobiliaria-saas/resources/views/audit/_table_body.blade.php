@forelse ($activities as $activity)
    <tr class="align-top">
        <td class="px-6 py-4 text-stone-600">
            <div>{{ $activity->created_at?->format('Y-m-d H:i:s') }}</div>
            <div class="text-xs text-stone-400">{{ $activity->log_name }}</div>
        </td>
        <td class="px-6 py-4 text-stone-900">
            <div class="font-semibold">{{ $activity->event ?: 'sin evento' }}</div>
            <div class="text-stone-500">{{ $activity->description_label }}</div>
        </td>
        <td class="px-6 py-4 text-stone-600">
            <div>{{ $activity->causer?->name ?: 'Sistema' }}</div>
            <div class="text-xs text-stone-400">{{ $activity->causer?->username ? '@'.$activity->causer->username : '' }}</div>
        </td>
        <td class="px-6 py-4 text-stone-600">
            <div>{{ $activity->company?->name ?: 'Sin empresa' }}</div>
            <div>{{ $activity->project?->name ?: 'Sin proyecto' }}</div>
        </td>
        <td class="px-6 py-4 text-stone-600">
            <div>{{ class_basename((string) $activity->subject_type) }}</div>
            <div class="text-xs text-stone-400">ID {{ $activity->subject_id ?: 'N/A' }}</div>
        </td>
        <td class="px-6 py-4 text-stone-600">
            <pre class="max-w-md overflow-x-auto whitespace-pre-wrap rounded-2xl bg-stone-50 px-3 py-2 text-xs text-stone-600">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </td>
    </tr>
@empty
    <tr data-empty-state>
        <td colspan="6" class="px-6 py-10 text-center text-stone-500">
            No se encontraron actividades con los filtros actuales.
        </td>
    </tr>
@endforelse
