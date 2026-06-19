@php
    $displayTitle = $section['title'] ?: ($section['default_title'] ?? $section['label']);
    $hasLimit = ($section['is_custom'] ?? false) || $section['default_item_limit'] !== null;
    $contentType = $section['content_type']
        ?? \Modules\BusinessSettingsModule\Services\MobileAppManagementService::sectionContentType((string) ($section['key'] ?? ''))
        ?? 'services';
    $supportsManual = ($section['supports_manual_data'] ?? false)
        || \Modules\BusinessSettingsModule\Services\MobileAppManagementService::sectionSupportsManualData(
            (string) ($section['key'] ?? ''),
            $contentType
        );
    $dataMode = $section['data_mode'] ?? 'default';
    $isCustom = $section['is_custom'] ?? false;
    $serviceIds = $section['service_ids'] ?? [];
    $providerIds = $section['provider_ids'] ?? [];
    $bannerIds = $section['banner_ids'] ?? [];
    $categoryIds = $section['category_ids'] ?? [];
    $campaignIds = $section['campaign_ids'] ?? [];
    $idx = $index ?? 0;
@endphp
<li class="mah-section-item {{ $section['enabled'] ? '' : 'is-disabled' }} {{ $isCustom ? 'is-custom' : '' }} {{ $dataMode === 'manual' ? 'is-manual-mode' : '' }} {{ ($expandSection ?? false) ? 'is-expanded' : '' }}"
    data-key="{{ $section['key'] }}"
    data-label="{{ $section['label'] }}"
    data-default-title="{{ $section['default_title'] ?? '' }}"
    data-preview-type="{{ $section['preview_type'] }}"
    data-conditional="{{ $section['conditional'] ?? '' }}"
    data-has-limit="{{ $hasLimit ? '1' : '0' }}"
    data-supports-manual="{{ $supportsManual ? '1' : '0' }}"
    data-content-type="{{ $contentType }}"
    data-is-custom="{{ $isCustom ? '1' : '0' }}">
    <div class="mah-section-head">
        <span class="material-icons mah-drag-handle" draggable="true" title="{{ translate('Drag_to_reorder') }}">drag_indicator</span>
        <button type="button" class="mah-accordion-toggle" aria-expanded="{{ ($expandSection ?? false) ? 'true' : 'false' }}" title="{{ translate('Click_section_to_expand') }}">
            <span class="material-icons mah-accordion-chevron">expand_more</span>
        </button>
        <div class="mah-section-icon">
            <span class="material-icons fz-20">{{ $section['icon'] }}</span>
        </div>
        <div class="mah-section-head-main">
            <div class="fw-semibold">{{ $section['label'] }}</div>
            <div class="mah-preview-label mah-item-preview-title">{{ $displayTitle }}</div>
        </div>
        @if($isCustom)
            <span class="badge bg-secondary-subtle text-secondary mah-custom-badge">{{ translate('Custom') }}</span>
        @endif
        @if($section['conditional'] ?? null)
            <span class="badge bg-info-subtle text-info mah-cond-badge mah-cond-label"
                  data-cond="{{ $section['conditional'] }}">
                {{ translate('Conditional') }}
            </span>
        @endif
        @if($isCustom)
            <button type="button" class="btn btn-sm btn-outline-danger mah-remove-custom" title="{{ translate('remove') }}">
                <span class="material-icons fz-16">delete</span>
            </button>
        @endif
        @if(!($section['fixed'] ?? false))
            <label class="form-check form-switch mb-0 ms-1">
                <input type="hidden" class="mah-enabled-hidden" name="sections[{{ $idx }}][enabled]" value="{{ $section['enabled'] ? '1' : '0' }}">
                <input class="form-check-input mah-enabled-toggle" type="checkbox" {{ $section['enabled'] ? 'checked' : '' }}>
            </label>
        @else
            <input type="hidden" class="mah-enabled-hidden" name="sections[{{ $idx }}][enabled]" value="1">
        @endif
    </div>
    <div class="mah-section-body">
        <input type="hidden" name="sections[{{ $idx }}][key]" value="{{ $section['key'] }}" class="mah-key-input">
        <input type="hidden" class="mah-sort-input" name="sections[{{ $idx }}][sort_order]" value="{{ $section['sort_order'] }}">
        @if($isCustom)
            <input type="hidden" name="sections[{{ $idx }}][content_type]" value="{{ $contentType }}" class="mah-content-type-input">
        @endif
        <div class="row g-2 mt-1">
            @if($isCustom)
                <div class="col-md-4">
                    <label class="form-label fz-12 mb-1">{{ translate('Section_type') }}</label>
                    <select class="form-select form-select-sm mah-custom-type-select">
                        <option value="services" {{ $contentType === 'services' ? 'selected' : '' }}>{{ translate('Services') }}</option>
                        <option value="providers" {{ $contentType === 'providers' ? 'selected' : '' }}>{{ translate('Providers') }}</option>
                        <option value="banners" {{ $contentType === 'banners' ? 'selected' : '' }}>{{ translate('Banners') }}</option>
                        <option value="campaigns" {{ $contentType === 'campaigns' ? 'selected' : '' }}>{{ translate('Campaigns') }}</option>
                        <option value="categories" {{ $contentType === 'categories' ? 'selected' : '' }}>{{ translate('Categories') }}</option>
                        <option value="sub_categories" {{ $contentType === 'sub_categories' ? 'selected' : '' }}>{{ translate('Sub_categories') }}</option>
                    </select>
                </div>
            @endif
            @if(($section['default_title'] ?? null) !== null || $section['key'] !== 'search' || $isCustom)
                <div class="col-md-{{ $isCustom ? 8 : 7 }}">
                    <label class="form-label fz-12 mb-1">{{ translate('Section_title') }}</label>
                    <input type="text" class="form-control form-control-sm mah-title-input"
                           name="sections[{{ $idx }}][title]"
                           value="{{ $section['title'] }}"
                           placeholder="{{ $section['default_title'] ?? $section['label'] }}">
                </div>
            @endif
            @if($hasLimit)
                <div class="col-md-{{ $isCustom ? 12 : 5 }}">
                    <label class="form-label fz-12 mb-1">{{ translate('Max_items') }}</label>
                    <input type="number" class="form-control form-control-sm mah-limit-input"
                           name="sections[{{ $idx }}][item_limit]"
                           value="{{ $section['item_limit'] }}"
                           min="1" max="50">
                </div>
            @endif
        </div>

        @if($supportsManual)
            @include('businesssettingsmodule::admin.mobile-app-management.partials.home-section-data-source', [
                'idx' => $idx,
                'sectionKey' => $section['key'],
                'dataMode' => $dataMode,
                'contentType' => $contentType,
                'serviceIds' => $serviceIds,
                'providerIds' => $providerIds,
                'bannerIds' => $bannerIds,
                'categoryIds' => $categoryIds,
                'campaignIds' => $campaignIds,
                'picklists' => $picklists,
            ])
        @endif


        <p class="text-muted fz-11 mb-0 mt-2">{{ $section['description'] }}</p>
    </div>
</li>
