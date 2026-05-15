@foreach ($asset2Types as $asset2Type)
    <option
        value="{{ $asset2Type['id'] }}"
        @disabled($asset2Type['status'] !== 'active')
        @selected((string) $selectedAsset2TypeId === (string) $asset2Type['id'])
    >
        {{ $asset2Type['name'] }}{{ $asset2Type['status'] !== 'active' ? ' (inactivo)' : '' }}
    </option>
@endforeach
