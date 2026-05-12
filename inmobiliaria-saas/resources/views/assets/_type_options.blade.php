@foreach ($assetTypes as $assetType)
    <option
        value="{{ $assetType['id'] }}"
        @disabled($assetType['status'] !== 'active')
        @selected((string) $selectedAssetTypeId === (string) $assetType['id'])
    >
        {{ $assetType['name'] }}{{ $assetType['status'] !== 'active' ? ' (inactivo)' : '' }}
    </option>
@endforeach
