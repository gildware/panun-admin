@foreach($zoneTreeOptions as $zOpt)
    <option value="{{ $zOpt['id'] }}"
            data-zone-prefix="{{ $zOpt['prefix'] ?? '' }}"
            data-zone-name="{{ $zOpt['name'] ?? '' }}"
            data-zone-description="{{ $zOpt['description'] ?? '' }}"
            @if(!empty($selected) && (string) $selected === (string) $zOpt['id']) selected @endif
            @if(!empty($autoSelectSingle) && count($zoneTreeOptions) === 1 && $loop->first) selected @endif>{{ $zOpt['label'] }}</option>
@endforeach
