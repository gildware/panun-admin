@php
    $variants = $iconVariants ?? \Modules\BusinessSettingsModule\Services\MobileAppManagementService::ICON_VARIANTS;
    $storedVariants = $icons[$appKey][$def['key']] ?? ['light' => null, 'dark' => null];
    $previews = $iconPreviews[$appKey][$def['key']] ?? ['light' => null, 'dark' => null];
    $defaultAsset = \Modules\BusinessSettingsModule\Services\MobileAppManagementService::defaultIconAssetName($appKey, $def['key']);
@endphp
<div class="col-12">
    <div class="border rounded-10 p-3 h-100 mai-icon-field">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <label class="form-label fw-semibold mb-0">{{ $def['label'] }}</label>
                <div class="form-text">
                    {{ translate('Upload_separate_icons_for_light_and_dark_app_theme') }}
                    @if(($def['key'] ?? '') === 'provider_app_login_logo')
                        {{ translate('Provider_logo_login_hint') }}
                    @elseif(($def['key'] ?? '') === 'provider_app_home_logo')
                        {{ translate('Provider_logo_home_hint') }}
                    @endif
                </div>
            </div>
            <span class="badge bg-light text-dark border">{{ translate('App_default') }}: {{ $defaultAsset }}</span>
        </div>

        <div class="row g-3">
            @foreach($variants as $variant)
                @php
                    $field = "icon_{$appKey}_{$def['key']}_{$variant}";
                    $previewUrl = $previews[$variant] ?? null;
                    $hasCustom = !empty($storedVariants[$variant]);
                    $displayUrl = $hasCustom ? $previewUrl : null;
                    $bgClass = $variant === 'dark' ? 'mai-preview-dark' : 'mai-preview-light';
                    $variantLabel = $variant === 'dark' ? translate('Dark_theme') : translate('Light_theme');
                @endphp
                <div class="col-md-6">
                    <p class="fz-12 fw-semibold mb-2">{{ $variantLabel }}</p>

                    <div class="upload_wrapper d-flex justify-content-center {{ $bgClass }} rounded-10 p-3 position-relative">
                        @if($hasCustom)
                            <span class="badge bg-success position-absolute top-0 end-0 m-2 fz-10">{{ translate('Custom_upload') }}</span>
                        @endif
                        <div class="upload-file-new mai-icon-upload{{ $hasCustom ? ' input-disabled' : '' }}"
                             data-persisted-image="{{ $hasCustom ? 'true' : 'false' }}">
                            <input type="file"
                                   name="{{ $field }}"
                                   id="{{ $field }}"
                                   class="upload-file-new__input single_file_input"
                                   accept=".webp,.jpg,.jpeg,.png,.gif"
                                   data-crop-ratio="1:1">
                            <label class="upload-file-new__wrapper ratio-1-1" for="{{ $field }}">
                                <div class="upload-file-new-textbox text-center" @if($displayUrl) style="display:none" @endif>
                                    <div class="d-flex flex-column gap-1 justify-content-center">
                                        <i class="fi fi-sr-camera text-primary fs-16"></i>
                                        <span class="fs-10">{{ $hasCustom ? translate('Replace_image') : translate('Add_image') }}</span>
                                    </div>
                                </div>
                                <img class="upload-file-new-img"
                                     loading="lazy"
                                     src="{{ $displayUrl ?? '' }}"
                                     data-default-src="{{ $displayUrl ?? '' }}"
                                     data-src="{{ $displayUrl ?? '' }}"
                                     alt=""
                                     style="{{ $displayUrl ? 'display:block' : 'display:none' }}">
                            </label>
                            <div class="overlay @if($displayUrl) show @endif">
                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                        <i class="fi fi-rr-camera"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                        <i class="fi fi-sr-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="fs-10 mb-0 text-center text-muted mt-2">{{ translate('Recommended_square_png') }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
