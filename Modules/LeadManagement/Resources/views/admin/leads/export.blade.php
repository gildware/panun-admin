@extends('adminmodule::layouts.new-master')

@section('title', translate('Export').' '.$scopes[$scope])

@push('css_or_js')
    <style>
        .lead-export-wrap {
            width: 100%;
        }
        .lead-export-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            min-height: 0;
        }
        .lead-export-head__title {
            display: flex;
            align-items: baseline;
            gap: 8px;
            min-width: 0;
            margin: 0;
        }
        .lead-export-head .page-title {
            margin: 0;
            font-size: 1.05rem !important;
            font-weight: 650;
            line-height: 1.2;
            white-space: nowrap;
        }
        .lead-export-head__back {
            display: inline-flex;
            align-items: center;
            color: #667085;
            text-decoration: none;
            flex-shrink: 0;
        }
        .lead-export-head__back .material-icons {
            font-size: 20px;
        }
        .lead-export-head__back:hover {
            color: #24356b;
        }
        .lead-export-head__note {
            margin: 0;
            color: #667085;
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lead-export-wizard {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0 0 0 auto;
            padding: 0;
            list-style: none;
            flex-shrink: 0;
        }
        .lead-export-wizard li,
        .lead-export-wizard a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #98a2b3;
            text-decoration: none;
            white-space: nowrap;
        }
        .lead-export-wizard li {
            padding: 3px 8px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #e6e8ef;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
        }
        .lead-export-wizard .num {
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
        .lead-export-wizard li.is-current {
            background: #f5f7ff;
            border-color: #c9d4fb;
            color: #24356b;
        }
        .lead-export-wizard li.is-current .num,
        .lead-export-wizard li.is-done .num {
            background: #4158d0;
            color: #fff;
        }
        .lead-export-wizard li.is-done {
            color: #344054;
        }
        .lead-export-card {
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            padding: 12px 14px;
            margin-left: auto;
            margin-right: auto;
        }
        .lead-export-actions {
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
        .lead-export-card:has(.lead-export-actions) {
            padding-bottom: 76px;
        }
        body:has(.lead-export-actions) .footer {
            padding-bottom: 72px;
        }
        body:not(.nav-top) .lead-export-actions {
            inset-inline-start: 18rem;
        }
        body.aside-folded:not(.nav-top) .lead-export-actions {
            inset-inline-start: 4.6875rem;
        }
        .lead-export-intro {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }
        .lead-export-intro p {
            margin: 0;
            font-size: 12px;
        }
        .lead-export-drawer-backdrop,
        .lead-export-drawer {
            position: fixed;
            z-index: 1050;
        }
        .lead-export-drawer-backdrop {
            inset: 0;
            border: 0;
            padding: 0;
            background: rgba(16, 24, 40, .45);
            z-index: 1040;
        }
        .lead-export-drawer {
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
        .lead-export-drawer[hidden],
        .lead-export-drawer-backdrop[hidden] {
            display: none !important;
        }
        .lead-export-drawer__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e6e8ef;
        }
        .lead-export-drawer__head h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 650;
        }
        .lead-export-drawer__close {
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
        .lead-export-drawer__close:hover {
            background: #f2f4f7;
            color: #24356b;
        }
        .lead-export-drawer__body {
            flex: 1;
            overflow: auto;
            padding: 14px 16px 8px;
        }
        .lead-export-drawer__body .form-label {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .lead-export-drawer__body .form-control,
        .lead-export-drawer__body .form-select {
            min-height: 36px;
            font-size: 13px;
        }
        .lead-export-drawer__body .lead-export-field + .lead-export-field {
            margin-top: 10px;
        }
        .lead-export-drawer__body select[multiple] {
            min-height: 7.5rem;
        }
        .lead-export-drawer__foot {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            border-top: 1px solid #e6e8ef;
            background: #fff;
        }
        .lead-export-drawer__foot .btn {
            flex: 1;
        }
        .lead-export-filter-count {
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
        .lead-export-download-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-inline-start: auto;
        }
        .lead-export-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .lead-export-toolbar .form-control {
            flex: 1;
        }
        .lead-export-toggle {
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
        .lead-export-toggle:hover,
        .lead-export-toggle:focus-visible {
            background: #eef2ff;
            border-color: #c9d4fb;
            color: #1d2b57;
        }
        .lead-export-types,
        .lead-export-acc-list {
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
        .lead-export-types {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            padding: 16px;
        }
        .lead-export-type {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            min-height: 72px;
            padding: 20px 18px;
            border: 1px solid #e6e8ef;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            line-height: 1.35;
            cursor: pointer;
            color: #24356b;
            background: #fff;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }
        .lead-export-type:hover {
            background: #f5f7ff;
            border-color: #c9d4fb;
        }
        .lead-export-type:has(input:checked) {
            background: #f3f6ff;
            border-color: #8ea4f0;
            box-shadow: 0 0 0 1px #8ea4f0;
        }
        .lead-export-type input {
            margin: 0;
            flex-shrink: 0;
            width: 18px;
            height: 18px;
        }
        .lead-export-acc + .lead-export-acc {
            border-top: 1px solid #e6e8ef;
        }
        .lead-export-acc__bar {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fafbfe;
            padding: 6px 10px 6px 0;
            overflow: visible;
        }
        .lead-export-acc__bar .lead-export-toggle {
            flex: 0 0 auto;
            margin: 0;
            padding: 6px 12px;
            position: relative;
            z-index: 1;
        }
        .lead-export-acc__summary {
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
        .lead-export-acc__summary:hover {
            background: #f3f6ff;
        }
        .lead-export-acc__chevron {
            font-size: 18px;
            color: #667085;
            transition: transform .15s ease;
        }
        .lead-export-acc:not(.is-open) .lead-export-acc__chevron {
            transform: rotate(-90deg);
        }
        .lead-export-acc__title {
            font-size: 13px;
            font-weight: 650;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .lead-export-acc__count {
            font-size: 12px;
            font-weight: 600;
            color: #667085;
            font-variant-numeric: tabular-nums;
        }
        .lead-export-acc__panel {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 4px 16px;
            padding: 4px 16px 14px 44px;
            background: #fff;
        }
        .lead-export-acc__panel[hidden] {
            display: none;
        }
        .lead-export-option {
            display: flex;
            align-items: flex-start;
            gap: 4px;
            margin: 0;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.3;
        }
        .lead-export-option > label {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            flex: 0 1 auto;
            min-width: 0;
            margin: 0;
            cursor: pointer;
        }
        .lead-export-option:hover {
            background: #f5f7ff;
        }
        .lead-export-option:has(input:checked) {
            background: #f3f6ff;
        }
        .lead-export-option input {
            margin-top: 2px;
            flex-shrink: 0;
        }
        .lead-export-option.is-locked {
            background: #f8fafc;
        }
        .lead-export-option.is-locked > label {
            cursor: default;
        }
        .lead-export-option.is-locked small {
            display: block;
            color: #667085;
            font-size: 12px;
        }
        .lead-export-option__text {
            min-width: 0;
        }
        .lead-export-col {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
        }
        .lead-export-col__edit {
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
        .lead-export-col__edit svg {
            width: 13px;
            height: 13px;
            display: block;
        }
        .lead-export-col__edit:hover,
        .lead-export-col__edit:focus-visible {
            color: #24356b;
            background: #eef2ff;
        }
        .lead-export-col__input {
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
        .lead-export-preview thead th {
            position: sticky;
            top: 0;
            background: #f6f8fc;
            white-space: nowrap;
            z-index: 1;
        }
        .lead-export-preview td {
            white-space: nowrap;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .lead-export-scroll {
            overflow: auto;
            max-height: calc(100vh - 340px);
            border: 1px solid #e6e8ef;
            border-radius: 12px;
        }
        @media (max-width: 1199.98px) {
            body:not(.nav-top) .lead-export-actions,
            body.aside-folded:not(.nav-top) .lead-export-actions {
                inset-inline-start: 0;
            }
        }
        @media (max-width: 767.98px) {
            .lead-export-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .lead-export-types,
            .lead-export-acc__panel {
                grid-template-columns: 1fr;
                padding-left: 16px;
            }
            .lead-export-actions {
                padding-inline: 12px;
            }
            .lead-export-head {
                flex-wrap: wrap;
                gap: 6px;
            }
            .lead-export-wizard {
                width: 100%;
                margin-left: 0;
            }
            .lead-export-head__note {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $stepTitles = [
            1 => 'Choose which leads to export',
            2 => translate('Choose_the_columns_for_the_file'),
            3 => 'Preview the leads, then download',
        ];
        $filterCount = count(array_diff_key($sharedQuery, ['scope' => true]));
        $columnQuery = array_merge($sharedQuery, ['step' => 2, 'column_selection' => 1, 'columns' => $selectedKeys]);
        if ($customLabels !== []) {
            $columnQuery['labels'] = $customLabels;
        }
        $resetQuery = ['step' => 3, 'column_selection' => 1, 'columns' => $selectedKeys, 'scope' => $scope];
        if ($customLabels !== []) {
            $resetQuery['labels'] = $customLabels;
        }
        $hiddenQuery = function (array $query, array $except = []) {
            $html = '';
            foreach ($query as $name => $value) {
                if (in_array($name, $except, true)) {
                    continue;
                }
                foreach ((array) $value as $item) {
                    $field = is_array($value) ? $name.'[]' : $name;
                    $html .= '<input type="hidden" name="'.e($field).'" value="'.e($item).'">';
                }
            }
            return $html;
        };
    @endphp
    <div class="main-content">
        <div class="container-fluid lead-export-wrap">
            <div class="lead-export-head">
                <a href="{{ $backUrl }}" class="lead-export-head__back" data-turbo="false" title="{{ translate('All_Leads') }}">
                    <span class="material-icons">arrow_back</span>
                </a>
                <h2 class="lead-export-head__title page-title">
                    {{ translate('Export') }}
                    <span class="lead-export-head__note">{{ $stepTitles[$step] ?? '' }}</span>
                </h2>
                <ol class="lead-export-wizard">
                    <li class="{{ $step === 1 ? 'is-current' : 'is-done' }}">
                        @if($step > 1)
                            <a href="{{ route('admin.lead.export', array_merge($sharedQuery, ['step' => 1])) }}" class="lead-export-label-link" data-turbo="false">
                        @endif
                            <span class="num">1</span>
                            {{ $scopes[$scope] }}
                        @if($step > 1)
                            </a>
                        @endif
                    </li>
                    <li class="{{ $step === 2 ? 'is-current' : ($step > 2 ? 'is-done' : '') }}">
                        @if($step > 2)
                            <a href="{{ route('admin.lead.export', $columnQuery) }}" class="lead-export-label-link" data-turbo="false">
                        @endif
                            <span class="num">2</span>
                            {{ translate('Choose_columns') }}
                        @if($step > 2)
                            </a>
                        @endif
                    </li>
                    <li class="{{ $step === 3 ? 'is-current' : '' }}">
                        <span class="num">3</span>
                        {{ translate('Preview') }}
                    </li>
                </ol>
            </div>

            @if($step === 1)
                <form method="GET" action="{{ route('admin.lead.export') }}" class="lead-export-card" data-turbo="false">
                    <input type="hidden" name="step" value="2">
                    {!! $hiddenQuery($sharedQuery, ['scope']) !!}
                    <p class="text-muted mb-2" style="font-size:12px">Choose the leads to export. The next step lists only the fields that type is supposed to have.</p>
                    <div class="lead-export-types">
                        @foreach($scopes as $value => $label)
                            <label class="lead-export-type">
                                <input type="radio" name="scope" value="{{ $value }}" @checked($scope === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="lead-export-actions">
                        <a href="{{ $backUrl }}" class="btn btn--secondary" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 2)
                <form method="GET" action="{{ route('admin.lead.export') }}" class="lead-export-card" data-turbo="false" id="lead-export-step-form">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="column_selection" value="1">
                    {!! $hiddenQuery($sharedQuery) !!}
                    <p class="text-muted mb-2" style="font-size:12px">
                        @if($scope === 'all')
                            Every lead field is listed. A column stays blank when that lead does not use it.
                        @else
                            Only the fields a {{ $scopes[$scope] }} is supposed to have are listed.
                        @endif
                    </p>
                    <div class="lead-export-toolbar">
                        <input type="search" class="form-control" id="lead-export-column-search" placeholder="{{ translate('Search') }}">
                        <button type="button" class="lead-export-toggle" id="lead-export-toggle" data-mode="select">{{ translate('Select_all') }}</button>
                    </div>
                    <div class="lead-export-acc-list">
                        @foreach($groups as $group)
                            @php
                                $sectionOpen = $group['key'] === 'lead' || count(array_intersect(array_keys($group['columns']), $selectedKeys)) > 0;
                            @endphp
                            <section class="lead-export-acc {{ $sectionOpen ? 'is-open' : '' }}" data-group="{{ $group['key'] }}">
                                <div class="lead-export-acc__bar">
                                    <button type="button" class="lead-export-acc__summary" aria-expanded="{{ $sectionOpen ? 'true' : 'false' }}">
                                        <span class="material-icons lead-export-acc__chevron">expand_more</span>
                                        <span class="lead-export-acc__title">{{ $group['label'] }}</span>
                                        <span class="lead-export-acc__count" data-group-count="{{ $group['key'] }}"></span>
                                    </button>
                                    <button type="button" class="lead-export-toggle" data-group="{{ $group['key'] }}" data-mode="select">{{ translate('Select_all') }}</button>
                                </div>
                                <div class="lead-export-acc__panel" @if(! $sectionOpen) hidden @endif>
                                    @if($group['key'] === 'lead')
                                        <div class="lead-export-option is-locked" data-column-label="{{ strtolower(translate('Lead_Details')) }}">
                                            <label>
                                                <input type="checkbox" checked disabled>
                                                <span class="lead-export-option__text">
                                                    <span class="lead-export-option__name" data-default="{{ translate('Lead_Details') }}">{{ $customLabels['lead_link'] ?? translate('Lead_Details') }}</span>
                                                    <small>{{ translate('Always_included') }}</small>
                                                </span>
                                            </label>
                                            <button type="button" class="lead-export-col__edit" data-column-key="lead_link" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                    @foreach($group['columns'] as $key => $label)
                                        @continue($key === 'lead_link')
                                        <div class="lead-export-option" data-column-label="{{ strtolower($group['label'].' '.$label) }}">
                                            <label>
                                                <input type="checkbox" name="columns[]" value="{{ $key }}" data-export-group="{{ $group['key'] }}" {{ in_array($key, $selectedKeys, true) ? 'checked' : '' }}>
                                                <span class="lead-export-option__name" data-default="{{ $label }}">{{ $customLabels[$key] ?? $label }}</span>
                                            </label>
                                            <button type="button" class="lead-export-col__edit" data-column-key="{{ $key }}" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                    <input type="hidden" name="columns[]" value="lead_link">
                    @foreach($customLabels as $labelKey => $labelValue)
                        <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                    @endforeach
                    <div class="lead-export-actions">
                        <a href="{{ route('admin.lead.export', array_merge($sharedQuery, ['step' => 1])) }}" class="btn btn--secondary lead-export-label-link" data-turbo="false">{{ translate('Back') }}</a>
                        <button type="submit" class="btn btn--primary" id="lead-export-columns-next">{{ translate('Continue') }}</button>
                    </div>
                </form>
            @endif

            @if($step === 3)
                <div class="lead-export-card">
                    <div class="lead-export-intro">
                        <p class="text-muted">
                            @if($total === 0)
                                {{ translate('No_results_found') }}
                            @else
                                Showing the first {{ count($previewRows) }} of {{ $total }}. The file uses these columns and the filters you set.
                            @endif
                        </p>
                        <button type="button" class="lead-export-toggle" id="lead-export-filter-toggle" aria-expanded="false" aria-controls="lead-export-filters">
                            {{ translate('Filter') }}
                            @if($filterCount > 0)
                                <span class="lead-export-filter-count">{{ $filterCount }}</span>
                            @endif
                        </button>
                    </div>

                    <button type="button" class="lead-export-drawer-backdrop" id="lead-export-filter-backdrop" hidden aria-label="{{ translate('Close') }}"></button>
                    <form method="GET" action="{{ route('admin.lead.export') }}" id="lead-export-filters" class="lead-export-drawer" data-turbo="false" hidden>
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="column_selection" value="1">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        @foreach($selectedKeys as $key)
                            <input type="hidden" name="columns[]" value="{{ $key }}">
                        @endforeach
                        @foreach($customLabels as $labelKey => $labelValue)
                            <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                        @endforeach
                        <div class="lead-export-drawer__head">
                            <h3>{{ translate('Filter') }}</h3>
                            <button type="button" class="lead-export-drawer__close" id="lead-export-filter-close" aria-label="{{ translate('Close') }}">
                                <span class="material-icons">close</span>
                            </button>
                        </div>
                        <div class="lead-export-drawer__body">
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-search">{{ translate('Search') }}</label>
                                <input type="search" id="lead-export-search" name="search" class="form-control" value="{{ $search }}" placeholder="{{ translate('Search') }}">
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label">{{ translate('Lead_Source') }}</label>
                                <select name="source_id[]" class="form-select" multiple>
                                    @foreach($filterOptions['sources'] ?? [] as $source)
                                        <option value="{{ $source->id }}" @selected(in_array((string) $source->id, $selectedSourceIds, true))>{{ $source->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label">{{ translate('Ad_Source') }}</label>
                                <select name="ad_source_id[]" class="form-select" multiple>
                                    @foreach($filterOptions['adSources'] ?? [] as $adSource)
                                        <option value="{{ $adSource->id }}" @selected(in_array((string) $adSource->id, $selectedAdSourceIds, true))>{{ $adSource->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label">{{ translate('Handled_By') }}</label>
                                <select name="handled_by[]" class="form-select" multiple>
                                    <option value="{{ \Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE }}" @selected(in_array(\Modules\LeadManagement\Entities\Lead::FILTER_UNASSIGNED_VALUE, $selectedHandledBy, true))>{{ translate('Unassigned') }}</option>
                                    @foreach($filterOptions['employees'] ?? [] as $employee)
                                        @php $employeeName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? '')); @endphp
                                        <option value="{{ $employee->id }}" @selected(in_array((string) $employee->id, $selectedHandledBy, true))>{{ $employeeName !== '' ? $employeeName : $employee->email }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-status">{{ translate('Lead_Status') }}</label>
                                <select id="lead-export-status" name="lead_status" class="form-select">
                                    <option value="all" @selected($leadStatus === 'all')>{{ translate('All') }}</option>
                                    <option value="open" @selected($leadStatus === 'open')>{{ translate('Open') }}</option>
                                    <option value="closed" @selected($leadStatus === 'closed')>{{ translate('Closed') }}</option>
                                </select>
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-from">{{ translate('Recieved_On') }} ({{ translate('From_Date') }})</label>
                                <input type="date" id="lead-export-from" name="date_from" class="form-control" value="{{ $dateFrom }}">
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-to">{{ translate('Recieved_On') }} ({{ translate('To_Date') }})</label>
                                <input type="date" id="lead-export-to" name="date_to" class="form-control" value="{{ $dateTo }}">
                            </div>
                            @if($showsFollowup)
                                <div class="lead-export-field">
                                    <label class="form-label" for="lead-export-followup-from">{{ translate('Followup_On') }} ({{ translate('From_Date') }})</label>
                                    <input type="datetime-local" id="lead-export-followup-from" name="followup_from" class="form-control" value="{{ $followupFrom }}">
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label" for="lead-export-followup-to">{{ translate('Followup_On') }} ({{ translate('To_Date') }})</label>
                                    <input type="datetime-local" id="lead-export-followup-to" name="followup_to" class="form-control" value="{{ $followupTo }}">
                                </div>
                            @endif
                            @if($showsTemperature)
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Lead_Temperature') }}</label>
                                    <select name="lead_temperature[]" class="form-select" multiple>
                                        @foreach(\Modules\LeadManagement\Entities\Lead::leadTemperatures() as $value => $label)
                                            <option value="{{ $value }}" @selected(in_array($value, $selectedTemperatures, true))>{{ translate($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($scope === 'customer')
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Status') }}</label>
                                    <select name="customer_status_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['customerStatuses'] ?? [] as $status)
                                            <option value="{{ $status->id }}" @selected(in_array((string) $status->id, $selectedCustomerStatusIds, true))>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Zone') }}</label>
                                    <select name="customer_zone_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['zones'] ?? [] as $zone)
                                            <option value="{{ $zone->id }}" @selected(in_array((string) $zone->id, $selectedCustomerZoneIds, true))>{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Category') }}</label>
                                    <select name="customer_category_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['categories'] ?? [] as $category)
                                            <option value="{{ $category->id }}" @selected(in_array((string) $category->id, $selectedCustomerCategoryIds, true))>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Sub_Category') }}</label>
                                    <select name="customer_sub_category_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['subcategories'] ?? [] as $category)
                                            <option value="{{ $category->id }}" @selected(in_array((string) $category->id, $selectedCustomerSubCategoryIds, true))>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Estimated_Date_Time_of_Service') }} ({{ translate('From_Date') }})</label>
                                    <input type="date" name="estimated_date_from" class="form-control" value="{{ $estimatedDateFrom }}">
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Estimated_Date_Time_of_Service') }} ({{ translate('To_Date') }})</label>
                                    <input type="date" name="estimated_date_to" class="form-control" value="{{ $estimatedDateTo }}">
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Booking_ID') }}</label>
                                    <select name="customer_has_booking" class="form-select">
                                        <option value="all" @selected($customerHasBooking === 'all')>{{ translate('All') }}</option>
                                        <option value="yes" @selected($customerHasBooking === 'yes')>{{ translate('Customer_lead_has_booking') }}</option>
                                        <option value="no" @selected($customerHasBooking === 'no')>{{ translate('Customer_lead_without_booking') }}</option>
                                    </select>
                                </div>
                            @endif
                            @if($scope === 'provider')
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Status') }}</label>
                                    <select name="status_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['providerStatuses'] ?? [] as $status)
                                            <option value="{{ $status->id }}" @selected(in_array((string) $status->id, $selectedProviderStatusIds, true))>{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('District') }}</label>
                                    <select name="district_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['districts'] ?? [] as $district)
                                            <option value="{{ $district->id }}" @selected(in_array((string) $district->id, $selectedDistrictIds, true))>{{ $district->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Zone') }}</label>
                                    <select name="zone_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['zones'] ?? [] as $zone)
                                            <option value="{{ $zone->id }}" @selected(in_array((string) $zone->id, $selectedZoneIds, true))>{{ $zone->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Service_Category') }}</label>
                                    <select name="category_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['categories'] ?? [] as $category)
                                            <option value="{{ $category->id }}" @selected(in_array((string) $category->id, $selectedCategoryIds, true))>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($scope === 'future_customer')
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Reason') }}</label>
                                    <select name="future_customer_reason_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['futureCustomerReasons'] ?? [] as $reason)
                                            <option value="{{ $reason->id }}" @selected(in_array((string) $reason->id, $selectedFutureCustomerReasonIds, true))>{{ $reason->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Outbound_Enquiries') }}</label>
                                    <select name="outbound_enquiry_filter" id="lead-export-outbound" class="form-select">
                                        <option value="all" @selected($outboundEnquiryFilter === 'all')>{{ translate('All') }}</option>
                                        <option value="none" @selected($outboundEnquiryFilter === 'none')>{{ translate('No_enquiries') }}</option>
                                        <option value="exact" @selected($outboundEnquiryFilter === 'exact')>{{ translate('Exact_number') }}</option>
                                        <option value="min" @selected($outboundEnquiryFilter === 'min')>{{ translate('At_least') }}</option>
                                    </select>
                                </div>
                                <div class="lead-export-field" id="lead-export-outbound-count" @if(! in_array($outboundEnquiryFilter, ['exact', 'min'], true)) hidden @endif>
                                    <label class="form-label">{{ translate('Number_of_enquiries') }}</label>
                                    <input type="number" min="0" name="outbound_enquiry_count" class="form-control" value="{{ $outboundEnquiryCount }}">
                                </div>
                            @endif
                            @if($scope === 'future_provider')
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Reason') }}</label>
                                    <select name="future_provider_reason_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['futureProviderReasons'] ?? [] as $reason)
                                            <option value="{{ $reason->id }}" @selected(in_array((string) $reason->id, $selectedFutureProviderReasonIds, true))>{{ $reason->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @if($scope === 'invalid')
                                <div class="lead-export-field">
                                    <label class="form-label">{{ translate('Reason') }}</label>
                                    <select name="invalid_reason_id[]" class="form-select" multiple>
                                        @foreach($filterOptions['invalidReasons'] ?? [] as $reason)
                                            <option value="{{ $reason->id }}" @selected(in_array((string) $reason->id, $selectedInvalidReasonIds, true))>{{ $reason->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-sort">{{ translate('sort_by') }}</label>
                                <select id="lead-export-sort" name="sort" class="form-select">
                                    <option value="latest" @selected($sort === 'latest')>{{ translate('Newest') }}</option>
                                    <option value="oldest" @selected($sort === 'oldest')>{{ translate('Oldest') }}</option>
                                    <option value="name_asc" @selected($sort === 'name_asc')>{{ translate('Name_A_Z') }}</option>
                                    <option value="name_desc" @selected($sort === 'name_desc')>{{ translate('Name_Z_A') }}</option>
                                </select>
                            </div>
                            <div class="lead-export-field">
                                <label class="form-label" for="lead-export-limit">{{ translate('customer_filter_max_results') }}</label>
                                <input type="number" min="1" id="lead-export-limit" name="limit" class="form-control" value="{{ $limit }}" placeholder="{{ translate('Export_max_results_hint') }}">
                            </div>
                        </div>
                        <div class="lead-export-drawer__foot">
                            <a href="{{ route('admin.lead.export', $resetQuery) }}" class="btn btn--secondary lead-export-label-link" data-turbo="false">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn--primary">{{ translate('Apply') }}</button>
                        </div>
                    </form>

                    <div class="lead-export-scroll">
                        <table class="table table-hover align-middle mb-0 lead-export-preview">
                            <thead>
                                <tr>
                                    @foreach($selectedKeys as $key)
                                        <th>
                                            <span class="lead-export-col">
                                                <span class="lead-export-col__name" data-default="{{ $columns[$key] ?? $key }}">{{ $customLabels[$key] ?? ($columns[$key] ?? $key) }}</span>
                                                <button type="button" class="lead-export-col__edit" data-column-key="{{ $key }}" aria-label="{{ translate('edit') }}" title="{{ translate('edit') }}">
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
                                            @php $cell = (string) ($row[$key] ?? ''); @endphp
                                            <td title="{{ $cell }}">{{ $cell !== '' ? $cell : '—' }}</td>
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

                    <div class="lead-export-actions">
                        <a href="{{ route('admin.lead.export', $columnQuery) }}" class="btn btn--secondary lead-export-label-link" data-turbo="false">{{ translate('Back') }}</a>
                        <form method="POST" action="{{ route('admin.lead.export.download') }}" class="lead-export-download-form" data-turbo="false" data-allow-duplicate-submit>
                            @csrf
                            <input type="hidden" name="column_selection" value="1">
                            {!! $hiddenQuery($sharedQuery) !!}
                            @foreach($selectedKeys as $key)
                                <input type="hidden" name="columns[]" value="{{ $key }}">
                            @endforeach
                            @foreach($customLabels as $labelKey => $labelValue)
                                <input type="hidden" name="labels[{{ $labelKey }}]" value="{{ $labelValue }}" data-label-key="{{ $labelKey }}">
                            @endforeach
                            <button type="submit" name="format" value="xlsx" class="btn btn--primary" data-no-loading @disabled($total === 0)>{{ translate('Download_Excel') }}</button>
                            <button type="submit" name="format" value="csv" class="btn btn--secondary" data-no-loading @disabled($total === 0)>{{ translate('CSV') }}</button>
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
            var form = document.getElementById('lead-export-step-form');
            if (!form) {
                return;
            }
            var boxes = Array.prototype.slice.call(form.querySelectorAll('input[type="checkbox"][name="columns[]"]'));
            var search = document.getElementById('lead-export-column-search');
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
                    var option = box.closest('.lead-export-option');
                    return !option || !option.hidden;
                });
            }
            function paintToggle(button, groupBoxes) {
                var allOn = groupBoxes.length > 0 && groupBoxes.every(function (box) { return box.checked; });
                button.textContent = allOn ? clearLabel : selectLabel;
                button.setAttribute('data-mode', allOn ? 'clear' : 'select');
            }
            function syncToggles() {
                form.querySelectorAll('.lead-export-toggle[data-group]').forEach(function (button) {
                    paintToggle(button, visibleBoxes(button.getAttribute('data-group')));
                });
                var pageToggle = document.getElementById('lead-export-toggle');
                if (pageToggle) {
                    paintToggle(pageToggle, visibleBoxes(null));
                }
                form.querySelectorAll('[data-group-count]').forEach(function (el) {
                    var groupBoxes = visibleBoxes(el.getAttribute('data-group-count'));
                    var checked = groupBoxes.filter(function (box) { return box.checked; }).length;
                    el.textContent = checked + '/' + groupBoxes.length;
                });
            }
            boxes.forEach(function (box) {
                box.addEventListener('change', syncToggles);
            });
            form.querySelectorAll('.lead-export-toggle').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    var turnOn = button.getAttribute('data-mode') !== 'clear';
                    visibleBoxes(button.getAttribute('data-group')).forEach(function (box) {
                        box.checked = turnOn;
                    });
                    syncToggles();
                });
            });
            form.querySelectorAll('.lead-export-acc__summary').forEach(function (summary) {
                summary.addEventListener('click', function () {
                    var section = summary.closest('.lead-export-acc');
                    var panel = section.querySelector('.lead-export-acc__panel');
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
                    form.querySelectorAll('.lead-export-option').forEach(function (option) {
                        var label = (option.getAttribute('data-column-label') || '') + ' ' + (option.textContent || '').toLowerCase();
                        option.hidden = needle !== '' && label.indexOf(needle) === -1;
                    });
                    form.querySelectorAll('.lead-export-acc').forEach(function (section) {
                        var visible = Array.prototype.filter.call(section.querySelectorAll('.lead-export-option'), function (option) {
                            return !option.hidden;
                        });
                        section.hidden = visible.length === 0;
                        if (needle !== '' && visible.length > 0) {
                            var panel = section.querySelector('.lead-export-acc__panel');
                            var summary = section.querySelector('.lead-export-acc__summary');
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
            var toggle = document.getElementById('lead-export-filter-toggle');
            var drawer = document.getElementById('lead-export-filters');
            var backdrop = document.getElementById('lead-export-filter-backdrop');
            var close = document.getElementById('lead-export-filter-close');
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
            toggle.addEventListener('click', function () { setOpen(drawer.hidden); });
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
            var outbound = document.getElementById('lead-export-outbound');
            var outboundCount = document.getElementById('lead-export-outbound-count');
            if (outbound && outboundCount) {
                outbound.addEventListener('change', function () {
                    outboundCount.hidden = outbound.value !== 'exact' && outbound.value !== 'min';
                });
            }
        })();

        (function () {
            var buttons = document.querySelectorAll('.lead-export-col__edit');
            if (!buttons.length) {
                return;
            }
            function writeLabel(key, value) {
                [document.getElementById('lead-export-step-form'), document.querySelector('.lead-export-download-form'), document.getElementById('lead-export-filters')].forEach(function (form) {
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
                document.querySelectorAll('a.lead-export-label-link').forEach(function (link) {
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
                    var host = button.closest('.lead-export-col, .lead-export-option');
                    if (!host || host.querySelector('.lead-export-col__input')) {
                        return;
                    }
                    var name = host.querySelector('.lead-export-col__name, .lead-export-option__name');
                    if (!name) {
                        return;
                    }
                    var fallback = name.getAttribute('data-default') || name.textContent.trim();
                    var key = button.getAttribute('data-column-key') || '';
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'lead-export-col__input';
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
            var form = document.querySelector('.lead-export-download-form');
            if (!form) {
                return;
            }
            var successText = @json(translate('Export_downloaded'));
            var failedText = @json(translate('Export_download_failed'));
            function toast(type, message) {
                if (!window.toastr || typeof window.toastr[type] !== 'function') {
                    return;
                }
                window.toastr[type](message, { CloseButton: true, ProgressBar: true });
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
                var fallbackName = submitter.value === 'csv' ? 'leads.csv' : 'leads.xlsx';
                buttons.forEach(function (button) { button.disabled = true; });
                submitter.classList.add('is-loading');
                submitter.setAttribute('aria-busy', 'true');
                submitter.textContent = '';
                var spin = document.createElement('span');
                spin.className = 'spinner-border spinner-border-sm me-1';
                spin.setAttribute('role', 'status');
                spin.setAttribute('aria-hidden', 'true');
                submitter.appendChild(spin);
                submitter.appendChild(document.createTextNode(' ' + original));
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
                        return { blob: blob, filename: filenameFrom(response, fallbackName) };
                    });
                }).then(function (file) {
                    var url = URL.createObjectURL(file.blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = file.filename;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                    toast('success', successText);
                }).catch(function () {
                    toast('error', failedText);
                }).finally(function () {
                    submitter.classList.remove('is-loading');
                    submitter.removeAttribute('aria-busy');
                    submitter.textContent = original;
                    buttons.forEach(function (button) { button.disabled = false; });
                });
            });
        })();
    </script>
@endpush
