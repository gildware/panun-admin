@extends('adminmodule::layouts.master')

@section('title', $pageTitle ?? translate('Export_providers'))

@push('css_or_js')
    <style>
        .provider-export-wrap {
            width: 100%;
        }
        .provider-export-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            min-height: 0;
        }
        .provider-export-head__title {
            display: flex;
            align-items: baseline;
            gap: 8px;
            min-width: 0;
            margin: 0;
        }
        .provider-export-head .page-title {
            margin: 0;
            font-size: 1.05rem !important;
            font-weight: 650;
            line-height: 1.2;
            white-space: nowrap;
        }
        .provider-export-head__back {
            display: inline-flex;
            align-items: center;
            color: #667085;
            text-decoration: none;
            flex-shrink: 0;
        }
        .provider-export-head__back .material-icons {
            font-size: 20px;
        }
        .provider-export-head__back:hover {
            color: #24356b;
        }
        .provider-export-head__note {
            margin: 0;
            color: #667085;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .provider-export-wizard {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0 0 0 auto;
            padding: 0;
            list-style: none;
            flex-shrink: 0;
        }
        .provider-export-wizard li,
        .provider-export-wizard a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #98a2b3;
            text-decoration: none;
            white-space: nowrap;
        }
        .provider-export-wizard li {
            padding: 3px 8px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e6e8ef;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
        }
        .provider-export-wizard .num {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            background: #f2f4f7;
            color: #98a2b3;
        }
        .provider-export-wizard li.is-current {
            background: #f5f7ff;
            border-color: #c9d4fb;
            color: #24356b;
        }
        .provider-export-wizard li.is-current .num,
        .provider-export-wizard li.is-done .num {
            background: #4158d0;
            color: #fff;
        }
        .provider-export-wizard li.is-done {
            color: #344054;
        }
        .provider-export-card {
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            padding: 12px 14px;
            margin-left: auto;
            margin-right: auto;
        }
        .provider-export-section-label {
            display: none;
        }
        .provider-export-filters .form-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .provider-export-filters .form-control,
        .provider-export-filters .form-select {
            min-height: 36px;
            font-size: 13px;
        }
        .provider-export-actions {
            position: fixed;
            z-index: 1028;
            bottom: 0;
            inset-inline: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding: 12px 20px;
            background: #fff;
            border-top: 1px solid #e6e8ef;
            box-shadow: 0 -8px 24px rgba(16, 24, 40, .08);
            flex-wrap: wrap;
        }
        .provider-export-card:has(.provider-export-actions) {
            padding-bottom: 76px;
        }
        body:has(.provider-export-actions) .footer {
            padding-bottom: 72px;
        }
        body:not(.nav-top) .provider-export-actions {
            inset-inline-start: 18rem;
        }
        body.aside-folded:not(.nav-top) .provider-export-actions {
            inset-inline-start: 4.6875rem;
        }
        .provider-export-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .provider-export-intro p {
            margin: 0;
            font-size: 12px;
        }
        .provider-export-drawer-backdrop,
        .provider-export-drawer {
            position: fixed;
            z-index: 1050;
        }
        .provider-export-drawer-backdrop {
            inset: 0;
            border: 0;
            padding: 0;
            background: rgba(16, 24, 40, .45);
            z-index: 1040;
        }
        .provider-export-drawer {
            top: 0;
            right: 0;
            width: min(400px, 100vw);
            height: 100vh;
            margin: 0;
            background: #fff;
            box-shadow: -12px 0 32px rgba(16, 24, 40, .16);
            display: flex;
            flex-direction: column;
        }
        .provider-export-drawer[hidden],
        .provider-export-drawer-backdrop[hidden] {
            display: none !important;
        }
        .provider-export-drawer__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e6e8ef;
        }
        .provider-export-drawer__head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 650;
        }
        .provider-export-drawer__close {
            border: 0;
            background: transparent;
            color: #667085;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .provider-export-drawer__close:hover {
            background: #f2f4f7;
            color: #24356b;
        }
        .provider-export-drawer__body {
            flex: 1;
            overflow: auto;
            padding: 14px 16px 8px;
        }
        .provider-export-drawer__body .form-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .provider-export-drawer__body .form-control,
        .provider-export-drawer__body .form-select {
            min-height: 36px;
            font-size: 13px;
        }
        .provider-export-drawer__body .provider-export-field + .provider-export-field {
            margin-top: 10px;
        }
        .provider-export-drawer__foot {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid #e6e8ef;
            background: #fff;
        }
        .provider-export-drawer__foot .btn {
            flex: 1;
        }
        .provider-export-filter-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 16px;
            height: 16px;
            margin-left: 6px;
            padding: 0 4px;
            border-radius: 999px;
            background: #4158d0;
            color: #fff;
            font-size: 10px;
            line-height: 1;
        }
        .provider-export-download-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-inline-start: auto;
        }
        .provider-export-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .provider-export-toolbar .form-control {
            flex: 1;
        }
        .provider-export-toggle {
            appearance: none;
            border: 1px solid #d0d5dd;
            background: #fff;
            color: #24356b;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            padding: 8px 14px;
            white-space: nowrap;
            cursor: pointer;
        }
        .provider-export-toggle:hover,
        .provider-export-toggle:focus-visible {
            background: #eef2ff;
            border-color: #c9d4fb;
            color: #1d2b57;
        }
        .provider-export-acc-list {
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
        .provider-export-acc + .provider-export-acc {
            border-top: 1px solid #e6e8ef;
        }
        .provider-export-acc__bar {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fafbfe;
            padding: 6px 10px 6px 0;
            overflow: visible;
        }
        .provider-export-acc__bar .provider-export-toggle {
            flex: 0 0 auto;
            margin: 0;
            padding: 6px 12px;
            position: relative;
            z-index: 1;
        }
        .provider-export-acc__summary {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            border: 0;
            background: transparent;
            text-align: start;
            padding: 7px 10px;
            cursor: pointer;
            color: #24356b;
        }
        .provider-export-acc__summary:hover {
            background: #f3f6ff;
        }
        .provider-export-acc__chevron {
            font-size: 18px;
            color: #667085;
            transition: transform .15s ease;
        }
        .provider-export-acc:not(.is-open) .provider-export-acc__chevron {
            transform: rotate(-90deg);
        }
        .provider-export-acc__title {
            font-size: 13px;
            font-weight: 650;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .provider-export-acc__count {
            font-size: 12px;
            font-weight: 600;
            color: #667085;
            font-variant-numeric: tabular-nums;
        }
        .provider-export-acc__panel {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 4px 16px;
            padding: 4px 16px 14px 44px;
            background: #fff;
        }
        .provider-export-acc__panel[hidden] {
            display: none;
        }
        .provider-export-option {
            display: flex;
            align-items: flex-start;
            gap: 4px;
            margin: 0;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.3;
        }
        .provider-export-option > label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex: 0 1 auto;
            min-width: 0;
            margin: 0;
            cursor: pointer;
        }
        .provider-export-option:hover {
            background: #f5f7ff;
        }
        .provider-export-option:has(input:checked) {
            background: #f3f6ff;
        }
        .provider-export-option input {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .provider-export-option.is-locked {
            background: #f8fafc;
        }
        .provider-export-option.is-locked > label {
            cursor: default;
        }
        .provider-export-option.is-locked small {
            display: block;
            color: #667085;
            font-size: 12px;
        }
        .provider-export-option__text {
            min-width: 0;
        }
        .provider-export-col {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
        }
        .provider-export-col__edit {
            border: 0;
            background: transparent;
            color: #98a2b3;
            width: 18px;
            height: 18px;
            margin-top: 1px;
            padding: 0;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .provider-export-col__edit svg {
            width: 13px;
            height: 13px;
            display: block;
        }
        .provider-export-col__edit:hover,
        .provider-export-col__edit:focus-visible {
            color: #24356b;
            background: #eef2ff;
        }
        .provider-export-col__input {
            width: 148px;
            max-width: 100%;
            min-height: 26px;
            font-size: 13px;
            font-weight: 600;
            padding: 2px 6px;
            border: 1px solid #c9d4fb;
            border-radius: 6px;
            color: #24356b;
        }
        @media (max-width: 1199.98px) {
            body:not(.nav-top) .provider-export-actions,
            body.aside-folded:not(.nav-top) .provider-export-actions {
                inset-inline-start: 0;
            }
        }
        @media (max-width: 767.98px) {
            .provider-export-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .provider-export-acc__panel {
                grid-template-columns: 1fr;
                padding-left: 16px;
            }
            .provider-export-actions {
                padding-inline: 12px;
            }
        }
        .provider-export-preview thead th {
            position: sticky;
            top: 0;
            background: #f6f8fc;
            white-space: nowrap;
            z-index: 1;
        }
        .provider-export-preview td {
            white-space: nowrap;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .provider-export-preview td.is-photo {
            white-space: normal;
            max-width: none;
            overflow: visible;
        }
        .provider-export-photos {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .provider-export-photos img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            background: #f2f4f7;
            border: 1px solid #e6e8ef;
        }
        .provider-export-scroll {
            overflow: auto;
            max-height: calc(100vh - 340px);
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
        .provider-export-hint {
            font-size: 12px;
            color: #667085;
            margin-top: 4px;
        }
        @media (max-width: 767.98px) {
            .provider-export-head {
                flex-wrap: wrap;
                gap: 6px;
            }
            .provider-export-wizard {
                width: 100%;
                margin-left: 0;
            }
            .provider-export-head__note {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $exportRoute = $exportRoute ?? 'admin.provider.export';
        $downloadRoute = $downloadRoute ?? 'admin.provider.export.download';
        $pageTitle = $pageTitle ?? translate('Export_providers');
        $backTitle = $backTitle ?? translate('Back_to_provider_list');
        $columnsHelp = $columnsHelp ?? translate('Export_providers_columns_help');
        $downloadNote = $downloadNote ?? translate('Export_providers_download_note');
        $requestStatusFilter = $requestStatusFilter ?? false;
        $requestStatus = $requestStatus ?? 'onboarding';
        $stepTitles = [
            1 => translate('Choose_the_columns_for_the_file'),
            2 => $stepTwoTitle ?? translate('Preview_the_providers_then_download'),
        ];
        $columnQuery = array_merge($sharedQuery, ['step' => 1, 'column_selection' => 1, 'columns' => $selectedKeys]);
        if ($customLabels !== []) {
            $columnQuery['labels'] = $customLabels;
        }
        $resetQuery = ['step' => 2, 'column_selection' => 1, 'columns' => $selectedKeys];
        if ($customLabels !== []) {
            $resetQuery['labels'] = $customLabels;
        }
        if ($requestStatusFilter) {
            $resetQuery['request_status'] = $requestStatus;
        }
        $filtersOpen = $sharedQuery !== [];
    @endphp
    <div class="main-content">
        <div class="container-fluid provider-export-wrap">
            <div class="provider-export-head">
                <a href="{{ $backUrl }}" class="provider-export-head__back" data-turbo="false" title="{{ $backTitle }}">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="provider-export-head__title page-title">
                    {{ $pageTitle }}
                    <span class="provider-export-head__note">{{ $stepTitles[$step] ?? '' }}</span>
                </h2>
                <ol class="provider-export-wizard">
                    <li class="{{ $step === 1 ? 'is-current' : 'is-done' }}">
                        @if($step > 1)
                            <a href="{{ route($exportRoute, $columnQuery) }}" class="provider-export-label-link" data-turbo="false">
                        @endif
                            <span class="num">1</span>
                            {{ translate('Choose_columns') }}
                        @if($step > 1)
                            </a>
                        @endif
                    </li>
                    <li class="{{ $step === 2 ? 'is-current' : '' }}">
                        <span class="num">2</span>
                        {{ translate('Preview') }}
                    </li>
                </ol>
            </div>

            @if($step === 1)
                <form method="GET" action="{{ route($exportRoute) }}" class="provider-export-card" data-turbo="false" id="provider-export-step-form">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="column_selection" value="1">
                    @foreach($sharedQuery as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <p class="text-muted mb-2" style="font-size:12px">{{ $columnsHelp }}</p>
                    <div class="provider-export-toolbar">
                        <input type="search" class="form-control" id="provider-export-column-search" placeholder="{{ translate('Search') }}">
                        <button type="button" class="provider-export-toggle" id="provider-export-toggle" data-mode="select">{{ translate('Select_all') }}</button>
                    </div>

                    <div class="provider-export-acc-list">
                        @foreach($groups as $group)
                            @php
                                $sectionOpen = $group['key'] === 'profile' || count(array_intersect(array_keys($group['columns']), $selectedKeys)) > 0;
                            @endphp
                            <section class="provider-export-acc {{ $sectionOpen ? 'is-open' : '' }}" data-group="{{ $group['key'] }}">
                                <div class="provider-export-acc__bar">
                                    <button type="button"
                                            class="provider-export-acc__summary"
                                            aria-expanded="{{ $sectionOpen ? 'true' : 'false' }}">
                                        <span class="material-icons provider-export-acc__chevron">expand_more</span>
                                        <span class="provider-export-acc__title">{{ $group['label'] }}</span>
                                        <span class="provider-export-acc__count" data-group-count="{{ $group['key'] }}"></span>
                                    </button>
                                    <button type="button"
                                            class="provider-export-toggle"
                                            data-group="{{ $group['key'] }}"
                                            data-mode="select">{{ translate('Select_all') }}</button>
                                </div>
                                <div class="provider-export-acc__panel" @if(! $sectionOpen) hidden @endif>
                                    @if($group['key'] === 'profile')
                                        <div class="provider-export-option is-locked" data-column-label="{{ strtolower(translate('Profile_link')) }}">
                                            <label>
                                                <input type="checkbox" checked disabled>
                                                <span class="provider-export-option__text">
                                                    <span class="provider-export-option__name" data-default="{{ translate('Profile_link') }}">{{ $customLabels['profile_link'] ?? translate('Profile_link') }}</span>
                                                    <small>{{ translate('Always_included') }}</small>
                                                </span>
                                            </label>
                                            <button type="button" class="provider-export-col__edit" data-column-key="profile_link" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                    @foreach($group['columns'] as $key => $label)
                                        @continue($key === 'profile_link')
                                        <div class="provider-export-option" data-column-label="{{ strtolower($group['label'].' '.$label) }}">
                                            <label>
                                                <input type="checkbox" name="columns[]" value="{{ $key }}" data-export-group="{{ $group['key'] }}" {{ in_array($key, $selectedKeys, true) ? 'checked' : '' }}>
                                                <span class="provider-export-option__name" data-default="{{ $label }}">{{ $customLabels[$key] ?? $label }}</span>
                                            </label>
                                            <button type="button" class="provider-export-col__edit" data-column-key="{{ $key }}" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                    <input type="hidden" name="columns[]" value="profile_link">
                    @foreach($customLabels as $labelKey => $labelValue)
                        <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                    @endforeach

                    <div class="provider-export-actions">
                        <a href="{{ $backUrl }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary" id="provider-export-columns-next">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 2)
                <div class="provider-export-card">
                    <div class="provider-export-intro">
                        <p class="text-muted">
                            @if($total === 0)
                                {{ translate('No_results_found') }}
                            @else
                                Showing the first {{ count($previewRows) }} of {{ $total }}. {{ $downloadNote }}
                            @endif
                        </p>
                        <button type="button"
                                class="provider-export-toggle"
                                id="provider-export-filter-toggle"
                                aria-expanded="false"
                                aria-controls="provider-export-filters">
                            {{ translate('Filter') }}
                            @if(count($sharedQuery) > 0)
                                <span class="provider-export-filter-count">{{ count($sharedQuery) }}</span>
                            @endif
                        </button>
                    </div>

                    <button type="button" class="provider-export-drawer-backdrop" id="provider-export-filter-backdrop" hidden aria-label="{{ translate('Close') }}"></button>
                    <form method="GET" action="{{ route($exportRoute) }}" id="provider-export-filters" class="provider-export-drawer" data-turbo="false" hidden>
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="column_selection" value="1">
                        @foreach($selectedKeys as $key)
                            <input type="hidden" name="columns[]" value="{{ $key }}">
                        @endforeach
                        @foreach($customLabels as $labelKey => $labelValue)
                            <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                        @endforeach
                        <div class="provider-export-drawer__head">
                            <h3>{{ translate('Filter') }}</h3>
                            <button type="button" class="provider-export-drawer__close" id="provider-export-filter-close" aria-label="{{ translate('Close') }}">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="provider-export-drawer__body">
                            @if($requestStatusFilter)
                                <div class="provider-export-field">
                                    <label class="form-label" for="provider-export-request-status">{{ translate('Request_status') }}</label>
                                    <select id="provider-export-request-status" name="request_status" class="form-select">
                                        <option value="all" @selected($requestStatus === 'all')>{{ translate('all') }}</option>
                                        <option value="onboarding" @selected($requestStatus === 'onboarding')>{{ translate('Onboarding_Requests') }}</option>
                                        <option value="denied" @selected($requestStatus === 'denied')>{{ translate('Denied_Requests') }}</option>
                                        <option value="accepted" @selected($requestStatus === 'accepted')>{{ translate('Accepted_Requests') }}</option>
                                    </select>
                                </div>
                            @endif
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-search">{{ translate('Search') }}</label>
                                <input type="search" id="provider-export-search" name="search" class="form-control" value="{{ $search }}" placeholder="{{ translate('search_here') }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-type">{{ translate('Provider_Type') }}</label>
                                <select id="provider-export-type" name="provider_type" class="form-select">
                                    <option value="all" @selected($providerType === 'all')>{{ translate('all') }}</option>
                                    <option value="individual" @selected($providerType === 'individual')>{{ translate('Individual') }}</option>
                                    <option value="company" @selected($providerType === 'company')>{{ translate('Company') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-status">{{ translate('status') }}</label>
                                <select id="provider-export-status" name="status" class="form-select">
                                    <option value="all" @selected($status === 'all')>{{ translate('all') }}</option>
                                    <option value="active" @selected($status === 'active')>{{ translate('active') }}</option>
                                    <option value="inactive" @selected($status === 'inactive')>{{ translate('inactive') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-service">{{ translate('Service_Availability') }}</label>
                                <select id="provider-export-service" name="service_availability" class="form-select">
                                    <option value="all" @selected($serviceAvailability === 'all')>{{ translate('all') }}</option>
                                    <option value="on" @selected($serviceAvailability === 'on')>{{ translate('active') }}</option>
                                    <option value="off" @selected($serviceAvailability === 'off')>{{ translate('inactive') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-app-availability">{{ translate('App_Availability') }}</label>
                                <select id="provider-export-app-availability" name="app_availability" class="form-select">
                                    <option value="all" @selected($appAvailability === 'all')>{{ translate('all') }}</option>
                                    <option value="on" @selected($appAvailability === 'on')>{{ translate('active') }}</option>
                                    <option value="off" @selected($appAvailability === 'off')>{{ translate('inactive') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-app">{{ translate('App') }}</label>
                                <select id="provider-export-app" name="app_filter" class="form-select">
                                    <option value="all" @selected($appFilter === 'all')>{{ translate('customer_filter_app_all') }}</option>
                                    <option value="active" @selected($appFilter === 'active')>{{ translate('customer_app_status_active') }}</option>
                                    <option value="registered" @selected($appFilter === 'registered')>{{ translate('customer_app_status_registered') }}</option>
                                    <option value="not_in_app" @selected($appFilter === 'not_in_app')>{{ translate('customer_app_status_not_in_app') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-suspended">{{ translate('Suspended') }}</label>
                                <select id="provider-export-suspended" name="suspended" class="form-select">
                                    <option value="all" @selected($suspended === 'all')>{{ translate('all') }}</option>
                                    <option value="yes" @selected($suspended === 'yes')>{{ translate('Yes') }}</option>
                                    <option value="no" @selected($suspended === 'no')>{{ translate('No') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-performance">{{ translate('Performance_Status') }}</label>
                                <select id="provider-export-performance" name="performance_filter" class="form-select">
                                    <option value="all" @selected($performanceFilter === 'all')>{{ translate('all') }}</option>
                                    <option value="warning" @selected($performanceFilter === 'warning')>{{ translate('Warning') }}</option>
                                    <option value="blacklisted" @selected($performanceFilter === 'blacklisted')>{{ translate('Blacklisted') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-rating">{{ translate('Rating') }}</label>
                                <select id="provider-export-rating" name="rating_filter" class="form-select">
                                    <option value="all" @selected($ratingFilter === 'all')>{{ translate('customer_filter_rating_all') }}</option>
                                    <option value="4_plus" @selected($ratingFilter === '4_plus')>{{ translate('customer_filter_rating_4_plus') }}</option>
                                    <option value="3_plus" @selected($ratingFilter === '3_plus')>{{ translate('customer_filter_rating_3_plus') }}</option>
                                    <option value="2_plus" @selected($ratingFilter === '2_plus')>{{ translate('customer_filter_rating_2_plus') }}</option>
                                    <option value="1_plus" @selected($ratingFilter === '1_plus')>{{ translate('customer_filter_rating_1_plus') }}</option>
                                    <option value="unrated" @selected($ratingFilter === 'unrated')>{{ translate('customer_filter_rating_unrated') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-category">{{ translate('Category') }}</label>
                                <select id="provider-export-category" name="category_id" class="form-select">
                                    <option value="">{{ translate('all') }}</option>
                                    <option value="none" @selected($categoryId === 'none')>{{ translate('No_Category') }}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-zone">{{ translate('Zone') }}</label>
                                <select id="provider-export-zone" name="zone_id" class="form-select">
                                    <option value="">{{ translate('all') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected($zoneId === (string) $zone->id)>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-city">{{ translate('city') }}</label>
                                <input type="text" id="provider-export-city" name="city" class="form-control" value="{{ $city }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-pincode">{{ translate('pincode') }}</label>
                                <input type="text" id="provider-export-pincode" name="pincode" class="form-control" value="{{ $pincode }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-identity">{{ translate('Identity_Type') }}</label>
                                <select id="provider-export-identity" name="identity_type" class="form-select">
                                    <option value="all" @selected($identityType === 'all')>{{ translate('all') }}</option>
                                    <option value="nid" @selected($identityType === 'nid')>{{ translate('nid') }}</option>
                                    <option value="passport" @selected($identityType === 'passport')>{{ translate('passport') }}</option>
                                    <option value="driving_license" @selected($identityType === 'driving_license')>{{ translate('driving_license') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-photo">{{ translate('Photos') }}</label>
                                <select id="provider-export-photo" name="photo" class="form-select">
                                    <option value="all" @selected($photo === 'all')>{{ translate('all') }}</option>
                                    <option value="logo" @selected($photo === 'logo')>{{ translate('logo') }}</option>
                                    <option value="cover" @selected($photo === 'cover')>{{ translate('cover_image') }}</option>
                                    <option value="contact" @selected($photo === 'contact')>{{ translate('Contact_Person_Photo') }}</option>
                                    <option value="none" @selected($photo === 'none')>{{ translate('No') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-commission">{{ translate('Commission') }}</label>
                                <select id="provider-export-commission" name="commission" class="form-select">
                                    <option value="all" @selected($commission === 'all')>{{ translate('all') }}</option>
                                    <option value="custom" @selected($commission === 'custom')>{{ translate('custom') }}</option>
                                    <option value="default" @selected($commission === 'default')>{{ translate('default') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-expertise">{{ translate('Expertise') }}</label>
                                <select id="provider-export-expertise" name="has_expertise" class="form-select">
                                    <option value="all" @selected($hasExpertise === 'all')>{{ translate('all') }}</option>
                                    <option value="yes" @selected($hasExpertise === 'yes')>{{ translate('Yes') }}</option>
                                    <option value="no" @selected($hasExpertise === 'no')>{{ translate('No') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-questionnaire">{{ translate('Questionnaire') }}</label>
                                <select id="provider-export-questionnaire" name="has_questionnaire" class="form-select">
                                    <option value="all" @selected($hasQuestionnaire === 'all')>{{ translate('all') }}</option>
                                    <option value="yes" @selected($hasQuestionnaire === 'yes')>{{ translate('Yes') }}</option>
                                    <option value="no" @selected($hasQuestionnaire === 'no')>{{ translate('No') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-min-bookings">{{ translate('Total_Bookings') }}</label>
                                <input type="number" min="1" id="provider-export-min-bookings" name="min_bookings" class="form-control" value="{{ $minBookings }}" placeholder="{{ translate('Total_Bookings') }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-from">{{ translate('customer_filter_joined_from') }}</label>
                                <input type="date" id="provider-export-from" name="from" class="form-control" value="{{ $from }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-to">{{ translate('customer_filter_joined_to') }}</label>
                                <input type="date" id="provider-export-to" name="to" class="form-control" value="{{ $to }}">
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-sort">{{ translate('sort_by') }}</label>
                                <select id="provider-export-sort" name="sort" class="form-select">
                                    <option value="latest" @selected($sort === 'latest')>{{ translate('Newest') }}</option>
                                    <option value="oldest" @selected($sort === 'oldest')>{{ translate('Oldest') }}</option>
                                    <option value="name_asc" @selected($sort === 'name_asc')>{{ translate('Name_A_Z') }}</option>
                                    <option value="name_desc" @selected($sort === 'name_desc')>{{ translate('Name_Z_A') }}</option>
                                    <option value="rating_desc" @selected($sort === 'rating_desc')>{{ translate('Highest_Rating') }}</option>
                                    <option value="bookings_desc" @selected($sort === 'bookings_desc')>{{ translate('Most_Bookings') }}</option>
                                </select>
                            </div>
                            <div class="provider-export-field">
                                <label class="form-label" for="provider-export-limit">{{ translate('customer_filter_max_results') }}</label>
                                <input type="number" min="1" id="provider-export-limit" name="limit" class="form-control" value="{{ $limit }}" placeholder="{{ translate('Export_max_results_hint') }}">
                            </div>
                        </div>
                        <div class="provider-export-drawer__foot">
                            <a href="{{ route($exportRoute, $resetQuery) }}" class="btn btn--secondary provider-export-label-link" data-turbo="false">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn--primary">{{ translate('Apply') }}</button>
                        </div>
                    </form>

                    <div class="provider-export-scroll">
                        <table class="table table-hover align-middle mb-0 provider-export-preview">
                            <thead>
                                <tr>
                                    @foreach($selectedKeys as $key)
                                        <th>
                                            <span class="provider-export-col">
                                                <span class="provider-export-col__name" data-default="{{ $columns[$key] ?? $key }}">{{ $customLabels[$key] ?? ($columns[$key] ?? $key) }}</span>
                                                <button type="button" class="provider-export-col__edit" data-column-key="{{ $key }}" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                                </button>
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($previewRows as $row)
                                    <tr>
                                        @foreach($selectedKeys as $key)
                                            @php
                                                $cell = (string) ($row[$key] ?? '');
                                                $isPhoto = in_array($key, $imageKeys, true);
                                                $photoUrls = [];
                                                if ($isPhoto && $cell !== '') {
                                                    foreach (preg_split('/\R/u', $cell) ?: [] as $photoUrl) {
                                                        $photoUrl = trim($photoUrl);
                                                        if (preg_match('#^https?://#i', $photoUrl)) {
                                                            $photoUrls[] = $photoUrl;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <td @class(['is-photo' => $isPhoto]) @unless($isPhoto) title="{{ $cell }}" @endunless>
                                                @if($isPhoto)
                                                    @if($photoUrls === [])
                                                        —
                                                    @else
                                                        <div class="provider-export-photos">
                                                            @foreach($photoUrls as $photoUrl)
                                                                <img src="{{ $photoUrl }}" alt="" loading="lazy" onerror="this.remove()">
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @else
                                                    {{ $cell !== '' ? $cell : '—' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ max(count($selectedKeys), 1) }}" class="text-center text-muted py-5">{{ translate('No_results_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="provider-export-actions">
                        <a href="{{ route($exportRoute, $columnQuery) }}" class="btn btn--secondary provider-export-label-link" data-turbo="false">{{ translate('Back') }}</a>
                        <form method="POST" action="{{ route($downloadRoute) }}" class="provider-export-download-form" data-turbo="false" data-allow-duplicate-submit>
                            @csrf
                            <input type="hidden" name="column_selection" value="1">
                            @foreach($sharedQuery as $name => $value)
                                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                            @endforeach
                            @foreach($selectedKeys as $key)
                                <input type="hidden" name="columns[]" value="{{ $key }}">
                            @endforeach
                            @foreach($customLabels as $labelKey => $labelValue)
                                <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                            @endforeach
                            <button type="submit" name="format" value="xlsx" class="btn btn--primary demo_check" data-no-loading @disabled($total === 0)>{{ translate('Download_Excel') }}</button>
                            <button type="submit" name="format" value="csv" class="btn btn--secondary demo_check" data-no-loading @disabled($total === 0)>{{ translate('CSV') }}</button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var form = document.getElementById('provider-export-step-form');
            if (!form) {
                return;
            }

            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[type="checkbox"][name="columns[]"]'));
            var next = document.getElementById('provider-export-columns-next');
            var search = document.getElementById('provider-export-column-search');
            var selectLabel = @json(translate('Select_all'));
            var clearLabel = @json(translate('Unselect_all'));

            function visibleBoxes(group) {
                return boxes.filter(function (box) {
                    if (box.disabled) {
                        return false;
                    }
                    if (group && box.dataset.exportGroup !== group) {
                        return false;
                    }
                    var option = box.closest('.provider-export-option');
                    return !option || !option.hidden;
                });
            }

            function paintToggle(button, groupBoxes) {
                var allOn = groupBoxes.length > 0 && groupBoxes.every(function (box) { return box.checked; });
                button.textContent = allOn ? clearLabel : selectLabel;
                button.setAttribute('data-mode', allOn ? 'clear' : 'select');
            }

            function syncToggles() {
                form.querySelectorAll('.provider-export-toggle[data-group]').forEach(function (button) {
                    paintToggle(button, visibleBoxes(button.getAttribute('data-group')));
                });
                var pageToggle = document.getElementById('provider-export-toggle');
                if (pageToggle) {
                    paintToggle(pageToggle, visibleBoxes(null));
                }
                form.querySelectorAll('[data-group-count]').forEach(function (el) {
                    var groupBoxes = visibleBoxes(el.getAttribute('data-group-count'));
                    var checked = groupBoxes.filter(function (box) { return box.checked; }).length;
                    el.textContent = checked + '/' + groupBoxes.length;
                });
                if (next) {
                    next.disabled = false;
                }
            }

            function setChecked(groupBoxes, checked) {
                groupBoxes.forEach(function (box) { box.checked = checked; });
                syncToggles();
            }

            boxes.forEach(function (box) {
                box.addEventListener('change', syncToggles);
            });

            form.querySelectorAll('.provider-export-toggle').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    var turnOn = button.getAttribute('data-mode') !== 'clear';
                    setChecked(visibleBoxes(button.getAttribute('data-group')), turnOn);
                });
            });

            form.querySelectorAll('.provider-export-acc__summary').forEach(function (summary) {
                summary.addEventListener('click', function () {
                    var section = summary.closest('.provider-export-acc');
                    var panel = section.querySelector('.provider-export-acc__panel');
                    var open = !section.classList.contains('is-open');
                    section.classList.toggle('is-open', open);
                    summary.setAttribute('aria-expanded', open ? 'true' : 'false');
                    if (panel) {
                        panel.hidden = !open;
                    }
                });
            });

            if (search) {
                search.addEventListener('input', function () {
                    var needle = (search.value || '').toLowerCase().trim();
                    form.querySelectorAll('.provider-export-option').forEach(function (option) {
                        if (option.classList.contains('is-locked')) {
                            option.hidden = needle !== '' && (option.textContent || '').toLowerCase().indexOf(needle) === -1;
                            return;
                        }
                        var nameEl = option.querySelector('.provider-export-option__name');
                        var label = (option.getAttribute('data-column-label') || '') + ' ' + (nameEl ? (nameEl.textContent || '').toLowerCase() : '');
                        option.hidden = needle !== '' && label.indexOf(needle) === -1;
                    });
                    form.querySelectorAll('.provider-export-acc').forEach(function (section) {
                        var visible = Array.prototype.filter.call(section.querySelectorAll('.provider-export-option'), function (option) {
                            return !option.hidden;
                        });
                        section.hidden = visible.length === 0;
                        if (needle !== '' && visible.length > 0) {
                            var panel = section.querySelector('.provider-export-acc__panel');
                            var summary = section.querySelector('.provider-export-acc__summary');
                            section.classList.add('is-open');
                            if (summary) {
                                summary.setAttribute('aria-expanded', 'true');
                            }
                            if (panel) {
                                panel.hidden = false;
                            }
                        }
                    });
                    syncToggles();
                });
            }

            syncToggles();
        })();

        (function () {
            var toggle = document.getElementById('provider-export-filter-toggle');
            var drawer = document.getElementById('provider-export-filters');
            var backdrop = document.getElementById('provider-export-filter-backdrop');
            var close = document.getElementById('provider-export-filter-close');
            if (!toggle || !drawer) {
                return;
            }
            function setOpen(open) {
                drawer.hidden = !open;
                if (backdrop) {
                    backdrop.hidden = !open;
                }
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                document.body.style.overflow = open ? 'hidden' : '';
            }
            toggle.addEventListener('click', function () {
                setOpen(drawer.hidden);
            });
            if (backdrop) {
                backdrop.addEventListener('click', function () { setOpen(false); });
            }
            if (close) {
                close.addEventListener('click', function () { setOpen(false); });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !drawer.hidden) {
                    setOpen(false);
                }
            });
        })();

        (function () {
            var buttons = document.querySelectorAll('.provider-export-col__edit');
            if (!buttons.length) {
                return;
            }

            function writeLabel(key, value) {
                [document.getElementById('provider-export-step-form'), document.querySelector('.provider-export-download-form'), document.getElementById('provider-export-filters')].forEach(function (form) {
                    if (!form) {
                        return;
                    }
                    var input = form.querySelector('input[type="hidden"][data-label-key="' + key + '"]');
                    if (value === '') {
                        if (input) {
                            input.remove();
                        }
                        return;
                    }
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'labels[' + key + ']';
                        input.setAttribute('data-label-key', key);
                        form.appendChild(input);
                    }
                    input.value = value;
                });

                document.querySelectorAll('a.provider-export-label-link').forEach(function (link) {
                    var href = link.getAttribute('href');
                    if (!href) {
                        return;
                    }
                    var url;
                    try {
                        url = new URL(href, window.location.origin);
                    } catch (e) {
                        return;
                    }
                    var param = 'labels[' + key + ']';
                    if (value === '') {
                        url.searchParams.delete(param);
                    } else {
                        url.searchParams.set(param, value);
                    }
                    link.setAttribute('href', url.pathname + url.search + url.hash);
                });
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    var host = button.closest('.provider-export-col, .provider-export-option');
                    if (!host || host.querySelector('.provider-export-col__input')) {
                        return;
                    }
                    var name = host.querySelector('.provider-export-col__name, .provider-export-option__name');
                    if (!name) {
                        return;
                    }
                    var fallback = name.getAttribute('data-default') || name.textContent.trim();
                    var key = button.getAttribute('data-column-key') || '';
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'provider-export-col__input';
                    input.value = name.textContent.trim();
                    input.maxLength = 80;
                    name.hidden = true;
                    button.hidden = true;
                    button.before(input);
                    input.focus();
                    input.select();
                    var done = false;
                    function commit(save) {
                        if (done) {
                            return;
                        }
                        done = true;
                        var next = save ? input.value.replace(/\s+/g, ' ').trim() : name.textContent.trim();
                        if (!next) {
                            next = fallback;
                        }
                        name.textContent = next;
                        name.hidden = false;
                        button.hidden = false;
                        input.remove();
                        if (save) {
                            writeLabel(key, next === fallback ? '' : next);
                        }
                    }
                    input.addEventListener('keydown', function (ev) {
                        if (ev.key === 'Enter') {
                            ev.preventDefault();
                            commit(true);
                        } else if (ev.key === 'Escape') {
                            ev.preventDefault();
                            commit(false);
                        }
                    });
                    input.addEventListener('blur', function () {
                        commit(true);
                    });
                });
            });
        })();

        (function () {
            var form = document.querySelector('.provider-export-download-form');
            if (!form) {
                return;
            }

            var successText = @json(translate('Export_downloaded'));
            var failedText = @json(translate('Export_download_failed'));
            var buildingText = @json(translate('Export_building_file'));

            function toast(type, message) {
                if (!window.toastr || typeof window.toastr[type] !== 'function') {
                    return;
                }
                window.toastr[type](message, {
                    CloseButton: true,
                    ProgressBar: true,
                    timeOut: type === 'error' ? 8000 : 4000
                });
            }

            function filenameFrom(response, fallback) {
                var disposition = response.headers.get('Content-Disposition') || '';
                var star = disposition.match(/filename\*=UTF-8''([^;]+)/i);
                if (star) {
                    try {
                        return decodeURIComponent(star[1].trim().replace(/^"|"$/g, ''));
                    } catch (e) {}
                }
                var plain = disposition.match(/filename="?([^";]+)"?/i);
                return plain ? plain[1].trim() : fallback;
            }

            function isFileResponse(response) {
                var disposition = response.headers.get('Content-Disposition') || '';
                var type = (response.headers.get('Content-Type') || '').toLowerCase();
                return /attachment/i.test(disposition)
                    || type.indexOf('spreadsheet') !== -1
                    || type.indexOf('excel') !== -1
                    || type.indexOf('csv') !== -1
                    || type.indexOf('octet-stream') !== -1;
            }

            function setBusy(submitter, buttons, original, busy) {
                buttons.forEach(function (button) {
                    button.disabled = busy;
                });
                if (!busy) {
                    submitter.classList.remove('is-loading');
                    submitter.removeAttribute('aria-busy');
                    submitter.textContent = original;
                    return;
                }
                submitter.classList.add('is-loading');
                submitter.setAttribute('aria-busy', 'true');
                submitter.textContent = '';
                var spin = document.createElement('span');
                spin.className = 'spinner-border spinner-border-sm me-1';
                spin.setAttribute('role', 'status');
                spin.setAttribute('aria-hidden', 'true');
                submitter.appendChild(spin);
                submitter.appendChild(document.createTextNode(' ' + (buildingText || original)));
            }

            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }
                event.preventDefault();

                var submitter = event.submitter || form.querySelector('button[type="submit"]');
                if (!submitter || submitter.disabled) {
                    return;
                }

                var buttons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"]'));
                var original = submitter.textContent.replace(/\s+/g, ' ').trim();
                var fallbackName = submitter.value === 'csv' ? 'providers.csv' : 'providers.xlsx';

                setBusy(submitter, buttons, original, true);

                var data = new FormData(form);
                if (submitter.name) {
                    data.set(submitter.name, submitter.value);
                }

                fetch(form.action, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/octet-stream, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, */*'
                    }
                }).then(function (response) {
                    if (!response.ok || !isFileResponse(response)) {
                        throw new Error('download failed');
                    }
                    return response.blob().then(function (blob) {
                        if (!blob || blob.size < 32) {
                            throw new Error('empty file');
                        }
                        return {
                            blob: blob,
                            filename: filenameFrom(response, fallbackName)
                        };
                    });
                }).then(function (file) {
                    var url = URL.createObjectURL(file.blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = file.filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(function () {
                        URL.revokeObjectURL(url);
                    }, 1000);
                    toast('success', successText);
                }).catch(function () {
                    toast('error', failedText);
                }).finally(function () {
                    setBusy(submitter, buttons, original, false);
                });
            });
        })();
    </script>
@endpush
