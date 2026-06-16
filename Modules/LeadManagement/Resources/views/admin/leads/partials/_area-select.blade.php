@php
    $areaSelectId = $areaSelectId ?? 'lead-area-select';
    $areaFieldName = $areaFieldName ?? 'area_id';
    $areaSelected = $areaSelected ?? '';
    $areaList = $areaList ?? ($customerLeadAreas ?? collect());
@endphp
<label class="form-label">{{ translate('Area') }}</label>
<select name="{{ $areaFieldName }}" id="{{ $areaSelectId }}" class="form-control lead-area-select" data-placeholder="{{ translate('Select_or_type_to_add_area') }}">
    <option value="">{{ translate('Select_or_type_to_add_area') }}</option>
    @foreach($areaList as $area)
        <option value="{{ $area->id }}" {{ (string) $areaSelected === (string) $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
    @endforeach
</select>
