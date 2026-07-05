@extends('adminmodule::layouts.new-master')

@section('title', translate('Home_Page'))

@push('css_or_js')
    <style>
        .mah-layout { display: grid; grid-template-columns: minmax(300px, 380px) 1fr; gap: 1.5rem; align-items: start; }
        @media (max-width: 1199px) { .mah-layout { grid-template-columns: 1fr; } }
        .mah-phone-wrap { position: sticky; top: 88px; }
        .mah-phone {
            width: 320px; max-width: 100%; margin: 0 auto;
            border: 10px solid #1a1a1a; border-radius: 36px;
            background: #f5f6f8; box-shadow: 0 24px 48px rgba(0,0,0,.12);
            overflow: hidden; min-height: 580px;
        }
        .mah-phone-notch {
            height: 28px; background: #1a1a1a;
            display: flex; align-items: flex-end; justify-content: center; padding-bottom: 4px;
        }
        .mah-phone-notch span { width: 72px; height: 6px; background: #333; border-radius: 6px; }
        .mah-phone-header {
            background: var(--bs-primary, #045462); color: #fff;
            padding: 10px 14px; display: flex; align-items: center; gap: 10px; font-size: 13px;
        }
        .mah-phone-header img { width: 28px; height: 28px; object-fit: contain; border-radius: 4px; background: #fff; }
        .mah-phone-scroll {
            max-height: 520px; overflow-y: auto; padding: 10px 12px 20px;
            background: linear-gradient(180deg, #fff 0%, #f8f9fa 100%);
        }
        .mah-preview-block { margin-bottom: 12px; animation: mahFadeIn .2s ease; }
        @keyframes mahFadeIn { from { opacity: .5; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        .mah-preview-search {
            display: flex; align-items: center; gap: 8px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 24px;
            padding: 10px 14px; font-size: 12px; color: #9ca3af;
        }
        .mah-preview-banner {
            height: 120px; border-radius: 12px; overflow: hidden; position: relative;
            background: #e5e7eb;
        }
        .mah-preview-banner img { width: 100%; height: 100%; object-fit: cover; }
        .mah-preview-banner-dots {
            position: absolute; bottom: 8px; left: 0; right: 0;
            display: flex; justify-content: center; gap: 4px;
        }
        .mah-preview-banner-dots span {
            width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.6);
        }
        .mah-preview-banner-dots span.active { background: #fff; width: 14px; border-radius: 4px; }
        .mah-preview-banner-slider { position: relative; }
        .mah-preview-banner-track {
            height: 120px; border-radius: 12px; overflow: hidden; position: relative;
            background: #e5e7eb;
        }
        .mah-preview-banner-slide {
            position: absolute; inset: 0; opacity: 0; transition: opacity .35s ease;
        }
        .mah-preview-banner-slide.active { opacity: 1; z-index: 1; }
        .mah-preview-title {
            font-size: 12px; font-weight: 600; margin-bottom: 8px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .mah-preview-title a { font-size: 10px; color: var(--bs-primary, #045462); text-decoration: none; }
        .mah-preview-cats { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 4px; }
        .mah-preview-cat {
            flex: 0 0 56px; text-align: center; font-size: 9px; color: #374151;
        }
        .mah-preview-cat .thumb {
            width: 48px; height: 48px; border-radius: 50%;
            background: #fff; border: 1px solid #e5e7eb; margin: 0 auto 4px;
            overflow: hidden; display: flex; align-items: center; justify-content: center;
        }
        .mah-preview-cat img { width: 100%; height: 100%; object-fit: cover; }
        .mah-preview-hscroll { display: flex; gap: 8px; overflow-x: auto; }
        .mah-preview-card {
            flex: 0 0 100px; background: #fff; border-radius: 10px;
            border: 1px solid #e5e7eb; overflow: hidden;
        }
        .mah-preview-card .img { height: 64px; background: #e5e7eb; overflow: hidden; }
        .mah-preview-card .img img,
        .mah-preview-cat .thumb img,
        .mah-preview-banner img,
        .mah-preview-highlight .av img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .mah-preview-highlight .av { overflow: hidden; }
        .mah-preview-card .txt { padding: 6px; font-size: 9px; font-weight: 500; }
        .mah-preview-highlight {
            display: flex; gap: 8px; padding: 10px; background: #fff;
            border-radius: 12px; border: 1px solid #e5e7eb;
        }
        .mah-preview-highlight .av {
            width: 40px; height: 40px; border-radius: 50%; background: #dbeafe;
        }
        .mah-preview-campaign {
            padding: 12px; border-radius: 12px;
            background: linear-gradient(135deg, #fef3c7, #fde68a); font-size: 11px;
        }
        .mah-preview-explore {
            padding: 16px; border-radius: 12px;
            background: linear-gradient(135deg, var(--bs-primary, #045462), #0a7a8c);
            color: #fff; font-size: 12px; text-align: center;
        }
        .mah-preview-post {
            padding: 14px; border-radius: 12px; border: 2px dashed #d1d5db;
            text-align: center; font-size: 11px; color: #6b7280;
        }
        .mah-preview-empty {
            padding: 24px; text-align: center; color: #9ca3af; font-size: 12px;
        }
        .mah-section-list { list-style: none; margin: 0; padding: 0; }
        .mah-section-item {
            border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 10px;
            background: #fff; transition: box-shadow .15s, opacity .15s;
        }
        .mah-section-item.is-disabled { opacity: .55; }
        .mah-section-item.is-dragging { opacity: .4; box-shadow: 0 8px 20px rgba(0,0,0,.1); }
        .mah-section-item.is-drag-over { border-color: var(--bs-primary, #045462); }
        .mah-section-head {
            display: flex; align-items: center; gap: 10px; padding: 12px 14px;
            user-select: none;
        }
        .mah-drag-handle {
            color: #9ca3af;
            cursor: grab;
            flex-shrink: 0;
            touch-action: none;
        }
        .mah-drag-handle:active { cursor: grabbing; }
        .mah-accordion-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #6b7280;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .mah-accordion-toggle:hover {
            background: #f3f4f6;
            color: var(--bs-primary, #045462);
        }
        .mah-accordion-chevron {
            font-size: 22px;
            transition: transform .2s ease;
        }
        .mah-section-item.is-expanded .mah-accordion-chevron {
            transform: rotate(180deg);
        }
        .mah-section-head-main {
            flex: 1;
            min-width: 0;
            cursor: pointer;
        }
        .mah-section-head-main:hover .fw-semibold {
            color: var(--bs-primary, #045462);
        }
        .mah-section-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: #f3f4f6; display: flex; align-items: center; justify-content: center;
        }
        .mah-section-body {
            display: none;
            padding: 0 14px 14px;
            border-top: 1px solid #f3f4f6;
        }
        .mah-section-item.is-expanded .mah-section-body { display: block; }
        .mah-section-item.is-expanded {
            border-color: rgba(var(--bs-primary-rgb, 4, 84, 98), 0.35);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }
        .mah-cond-badge { font-size: 10px; }
        .mah-preview-label { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .mah-data-panel { margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e5e7eb; }
        .mah-pick-select { width: 100%; }
        .mah-section-item.is-custom { border-style: dashed; }
        .mah-custom-badge { font-size: 10px; }
        .mah-segment {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 575px) {
            .mah-segment { grid-template-columns: 1fr; }
        }
        .mah-segment-option {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 0;
            padding: 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
            cursor: pointer;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }
        .mah-segment-option:hover {
            border-color: #cbd5e1;
            background: #fff;
        }
        .mah-segment-option.is-active {
            border-color: var(--bs-primary, #045462);
            background: rgba(var(--bs-primary-rgb, 4, 84, 98), 0.06);
            box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb, 4, 84, 98), 0.15);
        }
        .mah-segment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .mah-segment-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 20px;
        }
        .mah-segment-option.is-active .mah-segment-icon {
            background: var(--bs-primary, #045462);
            border-color: var(--bs-primary, #045462);
            color: #fff;
        }
        .mah-segment-text { flex: 1; min-width: 0; }
        .mah-segment-text strong {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            line-height: 1.3;
        }
        .mah-segment-text small {
            display: block;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.35;
            margin-top: 2px;
        }
        .mah-manual-picks-panel {
            margin-top: 12px;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .mah-manual-picks-panel.is-hidden { display: none; }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-20">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Home_Page') }}</h2>
                    <p class="fz-12 text-muted mb-0">{{ translate('Customer_app_home_page_sections_hint') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="mahResetOrder">{{ translate('Reset_to_default_order') }}</button>
                </div>
            </div>

            <form action="{{ route('admin.mobile-app-management.home-page.update') }}" method="POST" id="mahHomeForm">
                @csrf
                <div class="mah-layout">
                    <div class="mah-phone-wrap">
                        <div class="text-center mb-2">
                            <span class="badge bg-primary-subtle text-primary">{{ translate('Live_preview') }}</span>
                            <div class="fz-11 text-muted mt-1">{{ translate('Preview_updates_as_you_edit') }}</div>
                        </div>
                        <div class="mah-phone" id="mahPhone">
                            <div class="mah-phone-notch"><span></span></div>
                            <div class="mah-phone-header">
                                @if($businessLogo)
                                    <img src="{{ $businessLogo }}" alt="">
                                @endif
                                <span class="text-truncate">{{ $businessName }}</span>
                            </div>
                            <div class="mah-phone-scroll" id="mahPhoneScroll"></div>
                        </div>
                    </div>

                    <div>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong>{{ translate('Home_page_sections') }}</strong>
                                <span class="text-muted small">{{ translate('Drag_to_reorder') }} · {{ translate('Click_section_to_expand') }}</span>
                            </div>
                            <div class="card-body">
                                <ul class="mah-section-list" id="mahSectionList">
                                    @foreach($sections as $index => $section)
                                        @include('businesssettingsmodule::admin.mobile-app-management.partials.home-section-item', [
                                            'section' => $section,
                                            'index' => $index,
                                            'picklists' => $picklists,
                                        ])
                                    @endforeach
                                </ul>
                                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="mahAddSection">
                                    <span class="material-icons fz-16 align-middle">add</span>
                                    {{ translate('Add_custom_section') }}
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">{{ translate('save') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <script type="application/json" id="mahPreviewData">
        {!! json_encode(array_merge($previewPayload, [
            'flags' => $configFlags,
            'searchServicesUrl' => route('admin.mobile-app-management.home-page.search-services'),
            'searchProvidersUrl' => route('admin.mobile-app-management.home-page.search-providers'),
            'searchBannersUrl' => route('admin.mobile-app-management.home-page.search-banners'),
            'searchCampaignsUrl' => route('admin.mobile-app-management.home-page.search-campaigns'),
            'searchCategoriesUrl' => route('admin.mobile-app-management.home-page.search-categories'),
            'searchSubCategoriesUrl' => route('admin.mobile-app-management.home-page.search-sub-categories'),
            'dataSourceHints' => \Modules\BusinessSettingsModule\Services\MobileAppManagementService::dataSourceHintsForAdmin(),
        ])) !!}
    </script>

    <template id="mahCustomSectionTemplate">
        @include('businesssettingsmodule::admin.mobile-app-management.partials.home-section-item', [
            'section' => [
                'key' => 'custom_placeholder',
                'label' => translate('Custom_section'),
                'description' => translate('Custom_section_description'),
                'default_title' => translate('Custom_section'),
                'preview_type' => 'services_horizontal',
                'icon' => 'view_list',
                'enabled' => true,
                'fixed' => false,
                'sort_order' => 99,
                'title' => null,
                'item_limit' => 10,
                'conditional' => null,
                'supports_manual_data' => true,
                'content_type' => 'services',
                'is_custom' => true,
                'data_mode' => 'manual',
                'service_ids' => [],
                'provider_ids' => [],
            ],
            'index' => '__IDX__',
            'picklists' => ['services' => [], 'providers' => []],
        ])
    </template>
@endsection

@push('script')
    {{-- Select2 must load in the turbo frame before this page script (partial nav loads jQuery here, not in the global bundle). --}}
    <script src="{{ asset('assets/admin-module') }}/plugins/select2/select2.min.js"></script>
    <script>
        (function () {
            const previewData = JSON.parse(document.getElementById('mahPreviewData').textContent);
            const list = document.getElementById('mahSectionList');
            const phoneScroll = document.getElementById('mahPhoneScroll');
            const form = document.getElementById('mahHomeForm');
            let bannerSliderTimers = [];

            const condLabels = {
                direct_provider_booking: '{{ translate('Requires_direct_provider_booking') }}',
                bidding_status: '{{ translate('Requires_bidding_enabled') }}',
                logged_in: '{{ translate('Logged_in_customers_only') }}',
            };

            function sectionTitle(item) {
                const input = item.querySelector('.mah-title-input');
                const def = item.dataset.defaultTitle || item.dataset.label;
                const val = input ? input.value.trim() : '';
                return val || def || item.dataset.label;
            }

            function isCondVisible(conditional) {
                if (!conditional) return true;
                if (conditional === 'direct_provider_booking') return previewData.flags.direct_provider_booking === 1;
                if (conditional === 'bidding_status') return previewData.flags.bidding_status === 1;
                if (conditional === 'logged_in') return true;
                return true;
            }

            function escapeAttr(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }

            function catalogKeyForPickType(type) {
                if (type === 'providers') return 'providers';
                if (type === 'banners') return 'banners';
                if (type === 'campaigns') return 'campaigns';
                if (type === 'sub_categories') return 'sub_categories';
                if (type === 'categories') return 'categories';
                return 'services';
            }

            function sectionItemLimit(item, fallback) {
                const input = item.querySelector('.mah-limit-input');
                const parsed = parseInt(input?.value ?? '', 10);
                return Number.isFinite(parsed) && parsed > 0 ? parsed : (fallback || 10);
            }

            function sectionDataMode(item) {
                return item.querySelector('.mah-data-mode-radio:checked')?.value || 'default';
            }

            function cachePreviewItem(type, item) {
                if (!item?.id) return;
                const key = catalogKeyForPickType(type);
                if (!previewData.catalog) previewData.catalog = {};
                if (!previewData.catalog[key]) previewData.catalog[key] = {};
                previewData.catalog[key][item.id] = {
                    id: item.id,
                    name: item.text || item.name || item.id,
                    url: item.image || item.url || '',
                };
            }

            function getManualPickItems(item) {
                const pick = item.querySelector('.mah-pick-select');
                if (!pick) return [];
                const type = pick.dataset.pickType || 'services';
                const catKey = catalogKeyForPickType(type);
                const catalog = previewData.catalog?.[catKey] || {};
                let ids = $(pick).val();
                if (!ids) ids = [];
                if (!Array.isArray(ids)) ids = [ids];
                return ids.filter(Boolean).map(id => {
                    if (catalog[id]) return catalog[id];
                    const opt = [...pick.options].find(o => o.value === id);
                    return { id, name: opt?.text || id, url: opt?.dataset?.image || '' };
                });
            }

            function getSectionPreviewItems(item) {
                const key = item.dataset.key || '';
                const limit = sectionItemLimit(item, null);
                if (sectionDataMode(item) === 'manual') {
                    const manual = getManualPickItems(item);
                    return limit ? manual.slice(0, limit) : manual;
                }
                const defaults = previewData.defaults || {};
                let items = [];
                if (defaults[key]?.length) items = defaults[key];
                else if (key.startsWith('custom_')) {
                    const ct = item.dataset.contentType || 'services';
                    if (ct === 'banners') items = defaults.banners || [];
                    else if (ct === 'campaigns') items = defaults.campaigns || [];
                    else if (ct === 'categories') items = defaults.categories || [];
                    else if (ct === 'sub_categories') items = defaults.sub_categories || [];
                    else if (ct === 'providers') items = defaults.nearby_providers || [];
                    else items = defaults.popular_services || [];
                }
                return limit ? items.slice(0, limit) : items;
            }

            function renderPreviewImg(url, placeholderBg) {
                if (url) return '<img src="' + escapeAttr(url) + '" alt="" loading="lazy">';
                return '<span style="display:block;width:100%;height:100%;background:' + (placeholderBg || '#e5e7eb') + '"></span>';
            }

            function renderServiceCards(items, max) {
                const list = (items || []).slice(0, max || 5);
                if (!list.length) {
                    return '<div class="mah-preview-hscroll">' +
                        [1, 2, 3].map(() => '<div class="mah-preview-card"><div class="img"></div><div class="txt">Service</div></div>').join('') +
                        '</div>';
                }
                return '<div class="mah-preview-hscroll">' + list.map(s =>
                    '<div class="mah-preview-card"><div class="img">' + renderPreviewImg(s.url) + '</div><div class="txt">' + escapeHtml(s.name) + '</div></div>'
                ).join('') + '</div>';
            }

            function renderCategoryRow(items, max) {
                const list = (items || []).slice(0, max || 8);
                if (!list.length) {
                    return '<div class="mah-preview-cats">' +
                        [1, 2, 3, 4, 5].map(() => '<div class="mah-preview-cat"><div class="thumb"></div><span>Category</span></div>').join('') +
                        '</div>';
                }
                return '<div class="mah-preview-cats">' + list.map(c =>
                    '<div class="mah-preview-cat"><div class="thumb">' + renderPreviewImg(c.url) + '</div><span>' + escapeHtml(c.name) + '</span></div>'
                ).join('') + '</div>';
            }

            function renderImageSlider(items, placeholderText) {
                const list = items || [];
                if (!list.length) {
                    return '<div class="mah-preview-banner" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:11px;">' + placeholderText + '</div>';
                }
                const slides = list.map((item, i) =>
                    '<div class="mah-preview-banner-slide' + (i === 0 ? ' active' : '') + '">' + renderPreviewImg(item.url) + '</div>'
                ).join('');
                const dots = list.map((_, i) => '<span class="' + (i === 0 ? 'active' : '') + '" data-slide="' + i + '"></span>').join('');
                return '<div class="mah-preview-banner-slider" data-slide-count="' + list.length + '">' +
                    '<div class="mah-preview-banner-track">' + slides + '</div>' +
                    '<div class="mah-preview-banner-dots">' + dots + '</div></div>';
            }

            function renderBanners(items) {
                return renderImageSlider(items, '{{ translate('Banner_placeholder') }}');
            }

            function renderCampaigns(items) {
                return renderImageSlider(items, '{{ translate('Campaign_placeholder') }}');
            }

            function renderProviderCards(items, max) {
                const list = (items || []).slice(0, max || 4);
                if (!list.length) {
                    return '<div class="mah-preview-hscroll">' +
                        [1, 2].map(() => '<div class="mah-preview-card"><div class="img" style="background:#dbeafe"></div><div class="txt">Provider</div></div>').join('') +
                        '</div>';
                }
                return '<div class="mah-preview-hscroll">' + list.map(p =>
                    '<div class="mah-preview-card"><div class="img">' + renderPreviewImg(p.url, '#dbeafe') + '</div><div class="txt">' + escapeHtml(p.name) + '</div></div>'
                ).join('') + '</div>';
            }

            function renderPreviewBlock(item) {
                const enabled = item.querySelector('.mah-enabled-hidden')?.value === '1';
                if (!enabled) return '';

                const type = item.dataset.previewType;
                const title = sectionTitle(item);
                const key = item.dataset.key;
                const conditional = item.dataset.conditional;
                const condNote = conditional && !isCondVisible(conditional)
                    ? '<div class="fz-10 text-warning mt-1">' + (condLabels[conditional] || '') + '</div>' : '';

                let html = '<div class="mah-preview-block" data-preview-key="' + key + '">';

                switch (type) {
                    case 'search':
                        html += '<div class="mah-preview-search"><span class="material-icons" style="font-size:18px">search</span> {{ translate('Search_for_services') }}...</div>';
                        break;
                    case 'banner':
                        html += renderBanners(getSectionPreviewItems(item));
                        break;
                    case 'categories':
                    case 'categories_strip':
                    case 'sub_categories':
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span><a href="#">{{ translate('see_all') }}</a></div>';
                        html += renderCategoryRow(getSectionPreviewItems(item), type === 'categories_strip' ? 6 : 8);
                        break;
                    case 'highlight_providers':
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span></div>';
                        html += renderProviderCards(getSectionPreviewItems(item), 4);
                        break;
                    case 'services_horizontal':
                    case 'recommended':
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span><a href="#">{{ translate('see_all') }}</a></div>';
                        html += renderServiceCards(getSectionPreviewItems(item), 5);
                        break;
                    case 'providers_horizontal':
                    case 'providers_grid':
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span></div>';
                        html += renderProviderCards(getSectionPreviewItems(item), 4) + condNote;
                        break;
                    case 'campaign':
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span></div>';
                        html += renderCampaigns(getSectionPreviewItems(item));
                        break;
                    case 'explore_card':
                        html += '<div class="mah-preview-explore">' + escapeHtml(title) + '</div>' + condNote;
                        break;
                    case 'create_post':
                        html += '<div class="mah-preview-post"><span class="material-icons">add_circle_outline</span><br>' + escapeHtml(title) + condNote + '</div>';
                        break;
                    default: {
                        const ct = item.dataset.contentType || 'services';
                        html += '<div class="mah-preview-title"><span>' + escapeHtml(title) + '</span></div>';
                        if (ct === 'banners') {
                            html += renderBanners(getSectionPreviewItems(item));
                        } else if (ct === 'campaigns') {
                            html += renderCampaigns(getSectionPreviewItems(item));
                        } else if (ct === 'categories' || ct === 'sub_categories') {
                            html += renderCategoryRow(getSectionPreviewItems(item), 6);
                        } else if (ct === 'providers') {
                            html += renderProviderCards(getSectionPreviewItems(item), 4);
                        } else {
                            html += renderServiceCards(getSectionPreviewItems(item), 5);
                        }
                        break;
                    }
                }
                html += '</div>';
                return html;
            }

            function escapeHtml(s) {
                const d = document.createElement('div');
                d.textContent = s || '';
                return d.innerHTML;
            }

            function initBannerSliders() {
                bannerSliderTimers.forEach(timer => clearInterval(timer));
                bannerSliderTimers = [];

                phoneScroll.querySelectorAll('.mah-preview-banner-slider').forEach(slider => {
                    const slides = [...slider.querySelectorAll('.mah-preview-banner-slide')];
                    const dots = [...slider.querySelectorAll('.mah-preview-banner-dots span')];
                    if (slides.length <= 1) return;

                    let active = 0;
                    const show = (index) => {
                        active = index;
                        slides.forEach((slide, i) => slide.classList.toggle('active', i === index));
                        dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
                    };

                    dots.forEach(dot => {
                        dot.addEventListener('click', () => {
                            const idx = parseInt(dot.dataset.slide ?? '0', 10);
                            if (Number.isFinite(idx)) show(idx);
                        });
                    });

                    const timer = setInterval(() => show((active + 1) % slides.length), 3500);
                    bannerSliderTimers.push(timer);
                });
            }

            function refreshPreview() {
                const items = [...list.querySelectorAll('.mah-section-item')];
                let blocks = items.map(renderPreviewBlock).filter(Boolean);
                if (!blocks.length) {
                    blocks = ['<div class="mah-preview-empty">{{ translate('No_sections_enabled') }}</div>'];
                }
                phoneScroll.innerHTML = blocks.join('');
                initBannerSliders();
            }

            function reindexFormFields() {
                list.querySelectorAll('.mah-section-item').forEach((item, idx) => {
                    item.querySelector('.mah-sort-input').value = idx;
                    item.querySelectorAll('[name^="sections["]').forEach(el => {
                        el.name = el.name.replace(/sections\[\d+\]/, 'sections[' + idx + ']');
                    });
                    const label = item.querySelector('.mah-item-preview-title');
                    if (label) label.textContent = sectionTitle(item);
                });
            }

            function pickFieldName(type) {
                if (type === 'providers') return 'provider_ids';
                if (type === 'banners') return 'banner_ids';
                if (type === 'campaigns') return 'campaign_ids';
                if (type === 'categories' || type === 'sub_categories') return 'category_ids';
                return 'service_ids';
            }

            function pickSearchUrl(type) {
                if (type === 'providers') return previewData.searchProvidersUrl;
                if (type === 'banners') return previewData.searchBannersUrl;
                if (type === 'campaigns') return previewData.searchCampaignsUrl;
                if (type === 'sub_categories') return previewData.searchSubCategoriesUrl;
                if (type === 'categories') return previewData.searchCategoriesUrl;
                return previewData.searchServicesUrl;
            }

            function pickPlaceholder(type) {
                if (type === 'providers') return '{{ translate('Select_providers') }}';
                if (type === 'banners') return '{{ translate('Select_banners') }}';
                if (type === 'campaigns') return '{{ translate('Select_campaigns') }}';
                if (type === 'sub_categories') return '{{ translate('Select_sub_categories') }}';
                if (type === 'categories') return '{{ translate('Select_categories') }}';
                return '{{ translate('Select_services') }}';
            }

            function updateDataSourceHints(item) {
                const key = item.dataset.key || '';
                const contentType = item.dataset.contentType || 'services';
                const hints = previewData.dataSourceHints || {};
                const byKey = hints.byKey?.[key];
                const byType = hints.byContentType?.[contentType];
                const resolved = byKey || byType || {};
                const defaultEl = item.querySelector('.mah-default-hint');
                const manualEl = item.querySelector('.mah-manual-hint');
                if (defaultEl && resolved.default) defaultEl.textContent = resolved.default;
                if (manualEl && resolved.manual) manualEl.textContent = resolved.manual;
            }

            function isManualMode(item) {
                return item.querySelector('.mah-data-mode-radio:checked')?.value === 'manual';
            }

            function syncPickHidden(item) {
                const pick = item.querySelector('.mah-pick-select');
                const hiddenWrap = item.querySelector('.mah-pick-hidden');
                if (!hiddenWrap) return;
                hiddenWrap.innerHTML = '';
                if (!pick || !isManualMode(item)) return;
                const type = pick.dataset.pickType || 'services';
                const name = pickFieldName(type);
                const idxMatch = pick.closest('.mah-section-item')?.querySelector('[name^="sections["][name*="[key]"]')?.name.match(/sections\[(\d+)\]/);
                const prefix = idxMatch ? 'sections[' + idxMatch[1] + ']' : 'sections[0]';
                const values = $(pick).val();
                const ids = Array.isArray(values) ? values : (values ? [values] : []);
                ids.forEach(id => {
                    const trimmed = String(id || '').trim();
                    if (!trimmed) return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = prefix + '[' + name + '][]';
                    input.value = trimmed;
                    hiddenWrap.appendChild(input);
                });
            }

            function select2Ready() {
                return typeof window.jQuery !== 'undefined'
                    && typeof window.jQuery.fn !== 'undefined'
                    && typeof window.jQuery.fn.select2 === 'function';
            }

            function initPickSelect(item, force) {
                const select = item.querySelector('.mah-pick-select');
                if (!select) return;
                if (!force && select.dataset.mahPickInit === '1') return;
                if (!select2Ready()) {
                    select.dataset.mahPickInit = '0';
                    return;
                }

                const type = select.dataset.pickType || 'services';
                const url = pickSearchUrl(type);
                const $el = $(select);

                if ($el.data('select2')) {
                    $el.off('change.mahPick select2:select.mahPick select2:unselect.mahPick');
                    $el.select2('destroy');
                }

                select.dataset.mahPickInit = '1';

                $el.select2({
                    ajax: {
                        url: url,
                        dataType: 'json',
                        delay: 250,
                        cache: true,
                        data: params => ({ q: params.term ?? '' }),
                        processResults: data => ({
                            results: Array.isArray(data?.results) ? data.results : [],
                        }),
                    },
                    minimumInputLength: 0,
                    minimumResultsForSearch: 0,
                    dropdownParent: $(document.body),
                    placeholder: pickPlaceholder(type),
                    allowClear: true,
                    width: '100%',
                });

                $el.on('change.mahPick', () => {
                    syncPickHidden(item);
                    refreshPreview();
                });
                $el.on('select2:select.mahPick', e => {
                    cachePreviewItem(select.dataset.pickType || 'services', e.params.data);
                    refreshPreview();
                });
                $el.on('select2:unselect.mahPick', () => refreshPreview());
            }

            function resetPickSelect(item) {
                const pick = item.querySelector('.mah-pick-select');
                if (pick) pick.dataset.mahPickInit = '0';
                initPickSelect(item, true);
            }

            function syncDataSourceUi(item, options) {
                const opts = options || {};
                const value = item.querySelector('.mah-data-mode-radio:checked')?.value || 'default';
                const isManual = value === 'manual';
                item.classList.toggle('is-manual-mode', isManual);
                item.querySelectorAll('.mah-segment-option').forEach(opt => {
                    const input = opt.querySelector('input[type="radio"]');
                    opt.classList.toggle('is-active', input && input.value === value);
                });
                const panel = item.querySelector('.mah-manual-picks-panel');
                if (panel) panel.classList.toggle('is-hidden', !isManual);
                if (isManual) {
                    const shouldInitPick = opts.initPick === true
                        || (opts.initPick !== false && item.classList.contains('is-expanded'));
                    if (shouldInitPick) {
                        resetPickSelect(item);
                    }
                } else {
                    const hiddenWrap = item.querySelector('.mah-pick-hidden');
                    if (hiddenWrap) hiddenWrap.innerHTML = '';
                    const pick = item.querySelector('.mah-pick-select');
                    if (pick && $(pick).data('select2')) {
                        $(pick).val(null).trigger('change');
                    }
                }
            }

            function setAccordionExpanded(item, expanded) {
                item.classList.toggle('is-expanded', expanded);
                const btn = item.querySelector('.mah-accordion-toggle');
                if (btn) btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            }

            function toggleAccordion(item) {
                const willExpand = !item.classList.contains('is-expanded');
                setAccordionExpanded(item, willExpand);
                if (willExpand && isManualMode(item)) {
                    resetPickSelect(item);
                }
            }

            function bindAccordion(item) {
                if (item.dataset.mahAccordionInit === '1') return;
                item.dataset.mahAccordionInit = '1';
                item.querySelector('.mah-accordion-toggle')?.addEventListener('click', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleAccordion(item);
                });
                item.querySelector('.mah-section-head-main')?.addEventListener('click', e => {
                    e.preventDefault();
                    toggleAccordion(item);
                });
            }

            function bindDragHandle(item) {
                const handle = item.querySelector('.mah-drag-handle');
                if (!handle || handle.dataset.mahDragInit === '1') return;
                handle.dataset.mahDragInit = '1';
                handle.addEventListener('dragstart', e => {
                    dragItem = item;
                    item.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.stopPropagation();
                });
                handle.addEventListener('mousedown', e => e.stopPropagation());
                handle.addEventListener('click', e => e.stopPropagation());
            }

            function bindSectionItem(item) {
                bindAccordion(item);
                bindDragHandle(item);
                updateDataSourceHints(item);
                item.querySelectorAll('.mah-data-mode-radio').forEach(radio => {
                    radio.addEventListener('change', function () {
                        syncDataSourceUi(item, { initPick: true });
                        refreshPreview();
                    });
                });
                item.querySelectorAll('.mah-segment-option').forEach(opt => {
                    opt.addEventListener('click', function () {
                        const input = this.querySelector('input[type="radio"]');
                        if (input && !input.checked) {
                            input.checked = true;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                });
                syncDataSourceUi(item);

                item.querySelector('.mah-custom-type-select')?.addEventListener('change', function () {
                    const val = this.value;
                    item.dataset.contentType = val;
                    const previewMap = {
                        providers: 'providers_horizontal',
                        banners: 'banner',
                        campaigns: 'campaign',
                        categories: 'categories',
                        sub_categories: 'sub_categories',
                        services: 'services_horizontal',
                    };
                    item.dataset.previewType = previewMap[val] || 'services_horizontal';
                    const contentInput = item.querySelector('.mah-content-type-input');
                    if (contentInput) contentInput.value = val;
                    const pick = item.querySelector('.mah-pick-select');
                    if (pick) {
                        pick.dataset.pickType = val;
                        if ($(pick).data('select2')) {
                            $(pick).val(null).trigger('change');
                            pick.innerHTML = '';
                            $(pick).select2('destroy');
                        }
                        pick.dataset.mahPickInit = '0';
                        resetPickSelect(item);
                    }
                    const pickLabel = item.querySelector('.mah-pick-label');
                    if (pickLabel) pickLabel.textContent = pickPlaceholder(val);
                    item.querySelector('.mah-pick-hidden').innerHTML = '';
                    updateDataSourceHints(item);
                    refreshPreview();
                });

                item.querySelector('.mah-remove-custom')?.addEventListener('click', e => {
                    e.stopPropagation();
                    item.remove();
                    reindexFormFields();
                    refreshPreview();
                });

                const enabledToggle = item.querySelector('.mah-enabled-toggle');
                if (enabledToggle && enabledToggle.dataset.mahToggleInit !== '1') {
                    enabledToggle.dataset.mahToggleInit = '1';
                    enabledToggle.addEventListener('click', e => e.stopPropagation());
                    enabledToggle.addEventListener('change', function () {
                        const hidden = item.querySelector('.mah-enabled-hidden');
                        if (hidden) hidden.value = this.checked ? '1' : '0';
                        item.classList.toggle('is-disabled', !this.checked);
                        refreshPreview();
                    });
                }
            }

            function initAllSections() {
                list.querySelectorAll('.mah-section-item').forEach(item => {
                    try {
                        bindSectionItem(item);
                        syncPickHidden(item);
                    } catch (err) {
                        console.error('Home page section init failed:', item.dataset.key, err);
                    }
                });
            }

            function initExpandedPickSelects() {
                if (!select2Ready()) return;
                list.querySelectorAll('.mah-section-item.is-expanded').forEach(item => {
                    if (isManualMode(item)) {
                        resetPickSelect(item);
                    }
                });
            }

            function generateCustomKey() {
                return 'custom_'
                    + Math.random().toString(36).slice(2, 10)
                    + Math.random().toString(36).slice(2, 10);
            }

            document.getElementById('mahAddSection')?.addEventListener('click', () => {
                const tpl = document.getElementById('mahCustomSectionTemplate');
                const idx = list.querySelectorAll('.mah-section-item').length;
                let html = tpl.innerHTML.replace(/__IDX__/g, idx).replace(/custom_placeholder/g, generateCustomKey());
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                const item = wrap.firstElementChild;
                list.appendChild(item);
                reindexFormFields();
                bindSectionItem(item);
                const manualRadio = item.querySelector('.mah-data-mode-radio[value="manual"]');
                if (manualRadio) {
                    manualRadio.checked = true;
                }
                setAccordionExpanded(item, true);
                syncDataSourceUi(item, { initPick: true });
                refreshPreview();
            });

            form?.addEventListener('submit', () => {
                reindexFormFields();
                list.querySelectorAll('.mah-section-item').forEach(syncPickHidden);
            });

            list.querySelectorAll('.mah-title-input, .mah-limit-input').forEach(input => {
                input.addEventListener('input', function () {
                    const item = this.closest('.mah-section-item');
                    const label = item.querySelector('.mah-item-preview-title');
                    if (label) label.textContent = sectionTitle(item);
                    refreshPreview();
                });
            });

            let dragItem = null;
            list.querySelectorAll('.mah-section-item').forEach(item => {
                item.addEventListener('dragend', () => {
                    item.classList.remove('is-dragging');
                    list.querySelectorAll('.mah-section-item').forEach(i => i.classList.remove('is-drag-over'));
                    dragItem = null;
                    reindexFormFields();
                    refreshPreview();
                });
                item.addEventListener('dragover', e => {
                    e.preventDefault();
                    if (!dragItem || dragItem === item) return;
                    item.classList.add('is-drag-over');
                    const rect = item.getBoundingClientRect();
                    const next = (e.clientY - rect.top) > rect.height / 2;
                    list.insertBefore(dragItem, next ? item.nextSibling : item);
                });
                item.addEventListener('dragleave', () => item.classList.remove('is-drag-over'));
            });

            document.getElementById('mahResetOrder')?.addEventListener('click', () => {
                const defaultOrder = @json(collect(\Modules\BusinessSettingsModule\Services\MobileAppManagementService::homeSectionDefinitions())->pluck('key')->values());
                const items = [...list.querySelectorAll('.mah-section-item')];
                items.sort((a, b) => defaultOrder.indexOf(a.dataset.key) - defaultOrder.indexOf(b.dataset.key));
                items.forEach(i => list.appendChild(i));
                reindexFormFields();
                refreshPreview();
            });

            initAllSections();
            refreshPreview();
            initExpandedPickSelects();

            document.addEventListener('admin:page-loaded', function (event) {
                if (!event.detail?.root?.querySelector?.('#mahSectionList')) return;
                initExpandedPickSelects();
            });
        })();
    </script>
@endpush
