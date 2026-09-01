@extends('adminmodule::layouts.master')

@section('title', translate('Provider_Live_View'))

@php
    $plvAssetV = (@filemtime(public_path('assets/admin-module/js/provider-live-view.js')) ?: time()) . '-plv13';
    $plvCssV = (@filemtime(public_path('assets/admin-module/css/provider-live-view.css')) ?: time()) . '-plv13';
    $plvCalJsV = (@filemtime(public_path('assets/admin-module/js/provider-availability-calendar.js')) ?: time()) . '-plv13';
    $calendarWindowDays = (int) ($calendarWindowDays ?? 90);
    $calendarStartMaxDays = (int) ($calendarStartMaxDays ?? 365);
    $calendarFromDt = \Illuminate\Support\Carbon::parse($calendarFrom ?? now())->setTime(9, 0)->format('Y-m-d\TH:i');
    $calendarToDefault = \Illuminate\Support\Carbon::parse($calendarFrom ?? now())->addDays(6)->setTime(18, 0);
    $calendarStartMax = \Illuminate\Support\Carbon::parse($calendarFrom ?? now())->addDays($calendarStartMaxDays)->endOfDay();
    $calendarEndMax = \Illuminate\Support\Carbon::parse($calendarFrom ?? now())->addDays($calendarWindowDays)->endOfDay();
    if ($calendarToDefault->gt($calendarEndMax)) {
        $calendarToDefault = $calendarEndMax->copy()->setTime(18, 0);
    }
    $calendarToDt = $calendarToDefault->format('Y-m-d\TH:i');
    $calendarMinDt = \Illuminate\Support\Carbon::parse($calendarFrom ?? now())->startOfDay()->format('Y-m-d\TH:i');
    $calendarStartMaxDt = $calendarStartMax->format('Y-m-d\TH:i');
    $calendarEndMaxDt = $calendarEndMax->format('Y-m-d\TH:i');
    $plvLiveData = [
        'zones' => $zonesJson,
        'providers' => $providersJson,
        'defaultZoneId' => $defaultZoneId ?? null,
        'calendarFrom' => $calendarFrom ?? null,
        'calendarTo' => $calendarTo ?? null,
        'calendarFromDt' => $calendarFromDt,
        'calendarToDt' => $calendarToDt,
        'calendarWindowDays' => $calendarWindowDays,
        'calendarStartMaxDays' => $calendarStartMaxDays,
        'categories' => $categoriesJson ?? [],
        'subcategories' => $subcategoriesJson ?? [],
    ];
@endphp
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/provider-live-view.css') }}?v={{ $plvCssV }}">
    <style>
        .provider-live-page img.provider-live-avatar,
        .provider-live-page .provider-live-avatar-wrap,
        .provider-live-page .plv-popup-photo,
        .provider-live-page .plv-popup-photo img,
        .plv-float-card .plv-popup-photo,
        .plv-float-card .plv-popup-photo img,
        .plv-pin,
        .plv-pin img {
            border-radius: 50% !important;
            object-fit: cover !important;
            overflow: hidden !important;
        }
        .provider-live-page img.provider-live-avatar,
        .provider-live-page .provider-live-avatar-wrap {
            width: 42px !important;
            height: 42px !important;
            max-width: 42px !important;
        }
        .plv-popup-photo,
        .plv-popup-photo img {
            width: 48px !important;
            height: 48px !important;
        }
        .gm-style .gm-style-iw-ch {
            display: none !important;
            height: 0 !important;
            min-height: 0 !important;
            padding: 0 !important;
        }
        .gm-style .gm-style-iw-chr {
            position: absolute !important;
            top: 4px !important;
            right: 4px !important;
            height: 24px !important;
            width: 24px !important;
        }
        .gm-style .gm-ui-hover-effect {
            width: 24px !important;
            height: 24px !important;
        }
        .provider-live-page #plv-map-ui[hidden],
        .provider-live-page #plv-cal-ui[hidden],
        .provider-live-page #plv-subtitle-map[hidden],
        .provider-live-page #plv-subtitle-cal[hidden] {
            display: none !important;
        }
        .provider-live-page .provider-live-tabs {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 4px !important;
            background: #f4f6f9 !important;
            padding: 4px !important;
            border-radius: 10px !important;
            border: 0 !important;
        }
        .provider-live-page .provider-live-tabs button {
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            border: 0 !important;
            background: transparent !important;
            color: #64748b !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            width: auto !important;
        }
        .provider-live-page .provider-live-tabs button.on {
            background: #fff !important;
            color: #43466e !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .08) !important;
        }
    </style>
@endpush

@section('content')
    <div class="main-content provider-live-page">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div>
                    <h2 class="page-title mb-1">{{ translate('Provider_Live_View') }}</h2>
                    <p class="mb-0 text-muted fs-12" id="plv-subtitle-map">{{ translate('Find_providers_by_zone_category_availability_or_address') }}</p>
                    <p class="mb-0 text-muted fs-12" id="plv-subtitle-cal" hidden>{{ translate('Availability_calendar_help') }}</p>
                </div>
                <div class="provider-live-tabs" id="plv-tabs">
                    <button type="button" class="on" data-plv-tab="map"><span class="material-icons">map</span> {{ translate('Live_map') }}</button>
                    <button type="button" data-plv-tab="cal"><span class="material-icons">calendar_month</span> {{ translate('Availability_calendar') }}</button>
                </div>
            </div>

            <div id="plv-map-ui">
            <form class="provider-live-filters" onsubmit="return false;">
                <label class="fld">{{ translate('Search_by_name_or_address') }}
                    <input id="plv-q" type="search" class="form-control" placeholder="{{ translate('Search_provider_zone_or_address') }}">
                </label>
                <label class="fld">{{ translate('Zone') }} / {{ translate('Area') }}
                    <select id="plv-zone" class="form-select">
                        <option value="">{{ translate('All_zones') }}</option>
                        @foreach($zoneTreeOptions as $option)
                            <option value="{{ $option['id'] }}" @selected(($defaultZoneId ?? '') === $option['id'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="fld">{{ translate('Category') }}
                    <select id="plv-cat" class="form-select">
                        <option value="">{{ translate('All_categories') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="fld">{{ translate('Sub_Category') }}
                    <select id="plv-sub" class="form-select" disabled
                            data-placeholder-off="{{ translate('Select_a_category') }}"
                            data-placeholder-on="{{ translate('All_sub_categories') }}">
                        <option value="">{{ translate('Select_a_category') }}</option>
                    </select>
                </label>
                <label class="fld">{{ translate('Availability') }}
                    <select id="plv-avail" class="form-select">
                        <option value="">{{ translate('Any_status') }}</option>
                        <option value="available">{{ translate('Available_now') }}</option>
                        <option value="onjob">{{ translate('On_a_job') }}</option>
                        <option value="offline">{{ translate('Offline') }}</option>
                    </select>
                </label>
                <button class="btn btn-outline-primary" id="plv-reset" type="button">
                    <span class="material-icons">restart_alt</span> {{ translate('Reset') }}
                </button>
            </form>

            <div class="provider-live-kpis">
                <div class="provider-live-kpi" data-avail="">
                    <div class="l">{{ translate('Matching_providers') }}</div>
                    <div class="v" id="plv-k-total">0</div>
                    <div class="s">{{ translate('After_filters') }}</div>
                </div>
                <div class="provider-live-kpi good" data-avail="available">
                    <div class="l">{{ translate('Available_now') }}</div>
                    <div class="v" id="plv-k-avail">0</div>
                    <div class="s">{{ translate('Can_take_a_job') }}</div>
                </div>
                <div class="provider-live-kpi warn" data-avail="onjob">
                    <div class="l">{{ translate('On_a_job') }}</div>
                    <div class="v" id="plv-k-job">0</div>
                    <div class="s">{{ translate('Busy_in_field') }}</div>
                </div>
                <div class="provider-live-kpi bad" data-avail="offline">
                    <div class="l">{{ translate('Offline') }}</div>
                    <div class="v" id="plv-k-off">0</div>
                    <div class="s">{{ translate('Not_taking_work') }}</div>
                </div>
                <div class="provider-live-kpi">
                    <div class="l">{{ translate('Zones_covered') }}</div>
                    <div class="v" id="plv-k-zones">0</div>
                    <div class="s" id="plv-k-cover">{{ translate('Live_areas') }}</div>
                </div>
            </div>

            <div class="provider-live-workspace">
                <div class="provider-live-panel provider-live-panel--list">
                    <div class="provider-live-head">
                        <h3 class="provider-live-title">
                            <span class="material-icons">badge</span>
                            {{ translate('Providers') }}
                            <span class="provider-live-thin" id="plv-list-count"></span>
                        </h3>
                        <span class="provider-live-thin">{{ translate('Double_click_to_open') }}</span>
                    </div>
                    <div class="provider-live-list-body" id="plv-list"></div>
                </div>
                <div class="provider-live-panel provider-live-panel--map">
                    <div class="provider-live-head">
                        <h3 class="provider-live-title"><span class="material-icons">map</span> {{ translate('Coverage_map') }}</h3>
                        <div class="provider-live-seg" id="plv-map-mode">
                            <button type="button" class="on" data-mode="pins">{{ translate('Pins') }}</button>
                            <button type="button" data-mode="zones">{{ translate('Zone_heat') }}</button>
                        </div>
                    </div>
                    <div id="providerLiveMap"></div>
                    <div class="provider-live-legend">
                        <div><span class="provider-live-dot" style="background:#22c55e"></span> {{ translate('Available_now') }}</div>
                        <div><span class="provider-live-dot" style="background:#d97706"></span> {{ translate('On_a_job') }}</div>
                        <div><span class="provider-live-dot" style="background:#94a3b8"></span> {{ translate('Offline') }}</div>
                    </div>
                </div>
            </div>
            </div>

            <div id="plv-cal-ui" hidden>
                <form class="provider-cal-filters" onsubmit="return false;">
                    <label class="fld">{{ translate('Search_by_name_or_address') }}
                        <input id="plc-q" type="search" class="form-control" placeholder="{{ translate('Search_provider_zone_or_address') }}">
                    </label>
                    <label class="fld">{{ translate('Starts') }}
                        <input id="plc-from" type="datetime-local" class="form-control" value="{{ $calendarFromDt }}" min="{{ $calendarMinDt }}" max="{{ $calendarStartMaxDt }}" step="60">
                    </label>
                    <label class="fld">{{ translate('Ends') }}
                        <input id="plc-to" type="datetime-local" class="form-control" value="{{ $calendarToDt }}" min="{{ $calendarFromDt }}" max="{{ $calendarEndMaxDt }}" step="60">
                    </label>
                    <label class="fld">{{ translate('Zone') }} / {{ translate('Area') }}
                        <select id="plc-zone" class="form-select">
                            <option value="">{{ translate('All_zones') }}</option>
                            @foreach($zoneTreeOptions as $option)
                                <option value="{{ $option['id'] }}" @selected(($defaultZoneId ?? '') === $option['id'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fld">{{ translate('Category') }}
                        <select id="plc-cat" class="form-select">
                            <option value="">{{ translate('All_categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="fld">{{ translate('Sub_Category') }}
                        <select id="plc-sub" class="form-select" disabled
                                data-placeholder-off="{{ translate('Select_a_category') }}"
                                data-placeholder-on="{{ translate('All_sub_categories') }}">
                            <option value="">{{ translate('Select_a_category') }}</option>
                        </select>
                    </label>
                    <button class="btn btn-outline-primary" id="plc-reset" type="button">
                        <span class="material-icons">restart_alt</span> {{ translate('Reset') }}
                    </button>
                </form>
                <p class="provider-cal-note">{{ translate('Availability_calendar_note') }}</p>
                <div class="provider-cal-legend">
                    <span><i class="sw sw-free"></i> {{ translate('Free_in_window') }}</span>
                    <span><i class="sw sw-part"></i> {{ translate('Partial_slot') }}</span>
                    <span><i class="sw sw-sched"></i> {{ translate('Scheduled_job') }}</span>
                    <span><i class="sw sw-ong"></i> {{ translate('Ongoing_job') }}</span>
                    <span><i class="sw sw-off"></i> {{ translate('App_off_or_weekend') }}</span>
                </div>
                <div class="provider-live-kpis" id="plc-kpis"></div>
                <div class="provider-live-workspace provider-cal-workspace">
                    <div class="provider-live-panel provider-live-panel--list">
                        <div class="provider-live-head">
                            <h3 class="provider-live-title">
                                <span class="material-icons">badge</span>
                                {{ translate('Who_can_take_work') }}
                                <span class="provider-live-thin" id="plc-list-count"></span>
                            </h3>
                        </div>
                        <div class="provider-live-list-body" id="plc-list"></div>
                    </div>
                    <div class="provider-live-panel provider-cal-panel">
                        <div class="provider-live-head">
                            <h3 class="provider-live-title"><span class="material-icons">view_week</span> {{ translate('Range_calendar') }}</h3>
                            <span class="provider-live-thin" id="plc-range-label"></span>
                        </div>
                        <div class="provider-cal-table-wrap" id="plc-cal"></div>
                        <div class="provider-cal-detail" id="plc-detail" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="provider-live-data">{!! json_encode($plvLiveData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('script')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ business_config('google_map', 'third_party')?->live_values['map_api_key_client'] }}"></script>
    <script src="{{ asset('assets/admin-module/js/provider-live-view.js') }}?v={{ $plvAssetV }}" data-always-activate="1"></script>
    <script src="{{ asset('assets/admin-module/js/provider-availability-calendar.js') }}?v={{ $plvCalJsV }}" data-always-activate="1"></script>
@endpush

