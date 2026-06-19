@php
    use Modules\BusinessSettingsModule\Services\MobileAppManagementService;

    $idx = $idx ?? 0;
    $sectionKey = $sectionKey ?? '';
    $dataMode = $dataMode ?? 'default';
    $contentType = $contentType ?? 'services';
    $defaultHint = MobileAppManagementService::sectionDefaultDataHint($sectionKey, $contentType);
    $manualHint = MobileAppManagementService::sectionManualDataHint($sectionKey, $contentType);
    $serviceIds = $serviceIds ?? [];
    $providerIds = $providerIds ?? [];
    $bannerIds = $bannerIds ?? [];
    $categoryIds = $categoryIds ?? [];
    $campaignIds = $campaignIds ?? [];
    $picklists = $picklists ?? ['services' => [], 'providers' => [], 'banners' => [], 'categories' => [], 'sub_categories' => [], 'campaigns' => []];
    $isManual = $dataMode === 'manual';
    $pickType = in_array($contentType, ['providers', 'banners', 'categories', 'sub_categories', 'campaigns'], true) ? $contentType : 'services';
    $pickLabel = match ($pickType) {
        'providers' => translate('Select_providers'),
        'banners' => translate('Select_banners'),
        'campaigns' => translate('Select_campaigns'),
        'sub_categories' => translate('Select_sub_categories'),
        'categories' => translate('Select_categories'),
        default => translate('Select_services'),
    };
@endphp
<div class="mah-data-panel">
    <label class="form-label fz-12 mb-2 fw-semibold">{{ translate('Data_source') }}</label>
    <div class="mah-segment" role="radiogroup" aria-label="{{ translate('Data_source') }}">
        <label class="mah-segment-option {{ !$isManual ? 'is-active' : '' }}">
            <input type="radio"
                   class="mah-data-mode-radio"
                   name="sections[{{ $idx }}][data_mode]"
                   value="default"
                   {{ !$isManual ? 'checked' : '' }}>
            <span class="mah-segment-icon" aria-hidden="true">
                <span class="material-icons">auto_mode</span>
            </span>
            <span class="mah-segment-text">
                <strong>{{ translate('Use_default_app_data') }}</strong>
                <small class="mah-default-hint">{{ $defaultHint }}</small>
            </span>
        </label>
        <label class="mah-segment-option {{ $isManual ? 'is-active' : '' }}">
            <input type="radio"
                   class="mah-data-mode-radio"
                   name="sections[{{ $idx }}][data_mode]"
                   value="manual"
                   {{ $isManual ? 'checked' : '' }}>
            <span class="mah-segment-icon" aria-hidden="true">
                <span class="material-icons">checklist</span>
            </span>
            <span class="mah-segment-text">
                <strong>{{ translate('Pick_items_manually') }}</strong>
                <small class="mah-manual-hint">{{ $manualHint }}</small>
            </span>
        </label>
    </div>

    <div class="mah-manual-picks mah-manual-picks-panel {{ $isManual ? '' : 'is-hidden' }}">
        <label class="form-label fz-12 mb-1 fw-semibold mah-pick-label">{{ $pickLabel }}</label>
        <select class="form-control mah-pick-select" multiple data-pick-type="{{ $pickType }}">
            @if($pickType === 'providers')
                @foreach($providerIds as $pid)
                    <option value="{{ $pid }}" selected>{{ $picklists['providers'][$pid] ?? $pid }}</option>
                @endforeach
            @elseif($pickType === 'banners')
                @foreach($bannerIds as $bid)
                    <option value="{{ $bid }}" selected>{{ $picklists['banners'][$bid] ?? $bid }}</option>
                @endforeach
            @elseif($pickType === 'campaigns')
                @foreach($campaignIds as $cid)
                    <option value="{{ $cid }}" selected>{{ $picklists['campaigns'][$cid] ?? $cid }}</option>
                @endforeach
            @elseif($pickType === 'categories')
                @foreach($categoryIds as $cid)
                    <option value="{{ $cid }}" selected>{{ $picklists['categories'][$cid] ?? $cid }}</option>
                @endforeach
            @elseif($pickType === 'sub_categories')
                @foreach($categoryIds as $cid)
                    <option value="{{ $cid }}" selected>{{ $picklists['sub_categories'][$cid] ?? $cid }}</option>
                @endforeach
            @else
                @foreach($serviceIds as $sid)
                    <option value="{{ $sid }}" selected>{{ $picklists['services'][$sid] ?? $sid }}</option>
                @endforeach
            @endif
        </select>
        <div class="mah-pick-hidden">
            @if($pickType === 'providers')
                @foreach($providerIds as $pid)
                    <input type="hidden" name="sections[{{ $idx }}][provider_ids][]" value="{{ $pid }}">
                @endforeach
            @elseif($pickType === 'banners')
                @foreach($bannerIds as $bid)
                    <input type="hidden" name="sections[{{ $idx }}][banner_ids][]" value="{{ $bid }}">
                @endforeach
            @elseif($pickType === 'campaigns')
                @foreach($campaignIds as $cid)
                    <input type="hidden" name="sections[{{ $idx }}][campaign_ids][]" value="{{ $cid }}">
                @endforeach
            @elseif($pickType === 'categories' || $pickType === 'sub_categories')
                @foreach($categoryIds as $cid)
                    <input type="hidden" name="sections[{{ $idx }}][category_ids][]" value="{{ $cid }}">
                @endforeach
            @else
                @foreach($serviceIds as $sid)
                    <input type="hidden" name="sections[{{ $idx }}][service_ids][]" value="{{ $sid }}">
                @endforeach
            @endif
        </div>
        <p class="text-muted fz-11 mb-0 mt-2">{{ translate('Manual_picks_order_hint') }}</p>
    </div>
</div>
