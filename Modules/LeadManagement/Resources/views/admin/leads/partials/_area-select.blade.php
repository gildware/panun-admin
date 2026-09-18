@php
    $areaSelectId = $areaSelectId ?? 'lead-area-select';
    $areaMultiple = ! empty($areaMultiple);
    $areaFieldName = $areaFieldName ?? ($areaMultiple ? 'area_ids[]' : 'area_id');
    $areaSelected = $areaSelected ?? '';
    $areaList = $areaList ?? ($customerLeadAreas ?? collect());
    $areaSelectedIds = is_array($areaSelected)
        ? $areaSelected
        : (filled($areaSelected) ? [$areaSelected] : []);
    $areaSelectedIds = array_values(array_filter(array_map('strval', $areaSelectedIds), fn ($id) => $id !== ''));
    $knownAreaIds = collect($areaList)->map(fn ($area) => (string) $area->id)->all();
@endphp
<label class="form-label" for="{{ $areaSelectId }}">{{ translate('Area') }}</label>
<select name="{{ $areaFieldName }}"
        id="{{ $areaSelectId }}"
        class="form-control lead-area-select js-select-manual"
        data-placeholder="{{ translate('Select_or_type_to_add_area') }}"
        @if($areaMultiple) multiple @endif>
    @unless($areaMultiple)
        <option value="">{{ translate('Select_or_type_to_add_area') }}</option>
    @endunless
    @foreach($areaList as $area)
        <option value="{{ $area->id }}" {{ in_array((string) $area->id, $areaSelectedIds, true) ? 'selected' : '' }}>{{ $area->name }}</option>
    @endforeach
    @foreach($areaSelectedIds as $selectedArea)
        @if(! in_array((string) $selectedArea, $knownAreaIds, true))
            <option value="{{ $selectedArea }}" selected>{{ $selectedArea }}</option>
        @endif
    @endforeach
</select>
