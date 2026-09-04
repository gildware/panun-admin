@extends('adminmodule::layouts.new-master')

@section('title', translate('Hunting_Board'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/fullcalendar.css') }}">
@endpush

@section('content')
    <style>
        .hb-page-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem 1rem; flex-wrap: nowrap; }
        .hb-page-head__title { min-width: 0; flex: 1 1 220px; }
        .hb-page-head__title .page-title { margin-bottom: .15rem; }
        .hb-stat-row { display: flex; align-items: stretch; gap: .5rem; flex: 0 1 auto; }
        .hb-stat { display: flex; align-items: center; gap: .45rem; background: #fff; border: 1px solid #e2e5eb; border-radius: 8px; padding: .3rem .65rem; white-space: nowrap; }
        .hb-stat__label { font-size: .62rem; font-weight: 600; text-transform: uppercase; color: #a1a5af; letter-spacing: .03em; line-height: 1.1; }
        .hb-stat__value { font-size: .95rem; font-weight: 700; color: #18181a; line-height: 1; }
        .hb-view-toggle { flex-shrink: 0; }
        @media (max-width: 1100px) {
            .hb-page-head { flex-wrap: wrap; }
        }
        .hb-job { font-weight: 600; color: #18181a; }
        .hb-sub { display: block; font-size: .75rem; color: #6c757d; font-weight: 400; }
        .hb-lead-id { font-weight: 700; color: #25274D; }
        .hb-table { font-size: .8125rem; }
        .hb-table thead th { font-size: .75rem; font-weight: 600; white-space: nowrap; }
        .hb-filters { display: flex; flex-wrap: nowrap; align-items: flex-end; gap: .5rem; overflow-x: auto; }
        .hb-filters__search { flex: 1.4 1 160px; min-width: 140px; }
        .hb-filters__field { flex: 1 1 130px; min-width: 120px; }
        .hb-filters__actions { flex: 0 0 auto; display: flex; flex-direction: column; white-space: nowrap; }
        .hb-filters__buttons { display: flex; gap: .5rem; }
        .hb-filters .form-label { white-space: nowrap; }
        .hb-filters .select2-container { width: 100% !important; }
        .hb-view-toggle .btn { display: inline-flex; align-items: center; gap: .25rem; }
        .hb-view-toggle .btn .material-icons { font-size: 1.1rem; }
        .hb-page { padding-bottom: .75rem; }
        .hb-page--calendar { padding-bottom: 0; }
        body.nav-top .main-area:has(.hb-page--calendar) {
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
        }
        body.nav-top .main-area:has(.hb-page--calendar) > turbo-frame.admin-main-frame {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .hb-page--calendar {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .hb-page--calendar > .container-fluid {
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .hb-page--calendar .hb-page-head { margin-bottom: .5rem !important; gap: .5rem .75rem; }
        .hb-page--calendar .hb-page-head__title .page-title { font-size: 1.15rem; margin-bottom: 0; }
        .hb-page--calendar .hb-page-head__title .text-muted { display: none; }
        .hb-page--calendar .hb-stat { padding: .2rem .5rem; }
        .hb-page--calendar .card.mb-3 { margin-bottom: .5rem !important; }
        .hb-page--calendar .hb-filters .form-label { font-size: .65rem; margin-bottom: 0 !important; }
        .hb-page--calendar .hb-filters { gap: .4rem; }
        .hb-cal-card { overflow: hidden; }
        .hb-page--calendar .hb-cal-card {
            flex: 0 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
        }
        .hb-page--calendar .hb-cal-card .card-body {
            flex: 0 1 auto;
            min-height: 0;
            display: flex;
            flex-direction: column;
            padding: .35rem .6rem .4rem;
            overflow: hidden;
        }
        .hb-page--calendar .custom-booking-calendar {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }
        .hb-page--calendar #hb-calendar {
            flex: 1 1 auto;
            min-height: 0;
            width: 100%;
        }
        #hb-calendar { min-height: 0; }
        .hb-page--calendar #hb-calendar .fc { height: 100% !important; }
        .hb-page--calendar #hb-calendar .fc-header-toolbar {
            padding: 2px 0 8px !important;
            margin-bottom: 0 !important;
        }
        #hb-calendar .fc-view-harness {
            overflow: hidden !important;
        }
        .hb-page--calendar #hb-calendar .fc-view-harness {
            height: calc(100% - 2.75rem) !important;
        }
        .hb-page--calendar .custom-booking-calendar .fc-header-toolbar {
            padding: 0 0 6px !important;
        }
        .hb-page--calendar #hb-calendar .fc-col-header-cell .fc-scrollgrid-sync-inner {
            padding: 6px 4px 4px;
        }
        .hb-page--calendar #hb-calendar .fc-toolbar-title { font-size: .95rem; font-weight: 500; }
        #hb-calendar .fc-view-harness .fc-view { width: 100% !important; }
        #hb-calendar .fc-scrollgrid {
            border: 1px solid #dadce0 !important;
            border-radius: 8px;
        }
        #hb-calendar .fc-theme-standard td,
        #hb-calendar .fc-theme-standard th,
        #hb-calendar .fc-scrollgrid-sync-table td,
        #hb-calendar .fc-scrollgrid-sync-table th,
        #hb-calendar .fc-col-header-cell,
        #hb-calendar .fc-daygrid-day,
        #hb-calendar td.fc-daygrid-day,
        #hb-calendar .fc-timegrid-col,
        #hb-calendar .fc-timegrid-slot,
        #hb-calendar .fc-timegrid-axis {
            border: 1px solid #dadce0 !important;
        }
        #hb-calendar .fc-col-header-cell {
            background: #fff;
            border-bottom-color: #dadce0 !important;
        }
        #hb-calendar .fc-col-header-cell-cushion {
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #70757a;
            padding: 4px 0;
        }
        #hb-calendar .fc-scrollgrid-sync-table tbody tr td:nth-child(6),
        #hb-calendar .fc-scrollgrid-sync-table tbody tr td:nth-child(7) {
            background-color: #fff !important;
        }
        #hb-calendar .fc-daygrid-day-frame {
            overflow: hidden;
            min-height: 0;
            height: 100%;
            margin: 0;
            padding: 3px 4px 4px;
            box-sizing: border-box;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            position: relative;
        }
        #hb-calendar .fc-daygrid-day-bg,
        #hb-calendar .fc-daygrid-bg-harness {
            pointer-events: none !important;
            z-index: 0;
        }
        #hb-calendar .fc-daygrid-day-top {
            border: 0 !important;
            display: flex;
            justify-content: center;
            padding: 0 0 2px;
            flex: 0 0 auto;
        }
        #hb-calendar .fc-daygrid-day-number {
            float: none;
            font-weight: 500;
            font-size: .75rem;
            line-height: 1.4;
            padding: 2px 6px;
            color: #3c4043;
        }
        #hb-calendar .fc-day-other .fc-daygrid-day-number {
            color: #80868b;
        }
        #hb-calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number,
        #hb-calendar a.fc-daygrid-day-number {
            text-decoration: none;
        }
        .custom-booking-calendar #hb-calendar .fc .fc-daygrid-day.fc-day-today {
            background-color: transparent !important;
        }
        .custom-booking-calendar #hb-calendar .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number,
        #hb-calendar .fc-daygrid-day.fc-day-today a.fc-daygrid-day-number {
            background-color: #1a73e8 !important;
            color: #ffffff !important;
            width: 24px;
            height: 24px;
            padding: 0 !important;
            border-radius: 50%;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: .75rem;
        }
        #hb-calendar .fc-daygrid-day-events {
            margin: 0 !important;
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex-wrap: nowrap;
            gap: 1px;
            justify-content: flex-start;
            align-items: stretch;
            flex: 0 0 auto;
            position: relative;
            z-index: 6;
            pointer-events: auto !important;
        }
        #hb-calendar .fc-daygrid-event-harness,
        #hb-calendar .fc-daygrid-event-harness-abs {
            max-width: 100%;
            width: 100%;
        }
        #hb-calendar .fc-daygrid-event,
        #hb-calendar .fc-daygrid-block-event {
            display: block;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
            background: transparent;
            border: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 4px !important;
            cursor: pointer;
            pointer-events: auto !important;
            position: relative;
            z-index: 5;
        }
        #hb-calendar .fc-event-main,
        #hb-calendar .fc-event-main-frame {
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }
        #hb-calendar .fc-toolbar-title { font-size: 1.05rem; font-weight: 500; }
        #hb-calendar .fc-daygrid-day-frame {
            cursor: pointer;
        }
        #hb-calendar .fc-daygrid-event,
        #hb-calendar .fc-daygrid-block-event,
        #hb-calendar .hb-cal-event {
            cursor: pointer;
            pointer-events: auto;
            position: relative;
            z-index: 3;
        }
        .hb-day-job {
            display: block;
            width: 100%;
            text-align: left;
            border: 1px solid #e6e8ee;
            border-radius: 8px;
            padding: .65rem .75rem;
            background: #fff;
            margin: 0 0 .5rem;
        }
        .hb-day-job:hover { border-color: #1a73e8; background: #f8fbff; }
        .hb-day-job__top { display: flex; justify-content: space-between; gap: .5rem; align-items: baseline; }
        .hb-day-job__id { font-weight: 700; color: #25274D; }
        .hb-day-job__time { font-size: .8rem; color: #5f6368; font-weight: 600; }
        .hb-day-job__job { display: block; margin-top: .2rem; font-weight: 600; color: #18181a; }
        .hb-day-job__meta { display: block; margin-top: .15rem; font-size: .75rem; color: #6c757d; }
        #hb-calendar .hb-cal-event {
            display: flex;
            align-items: center;
            gap: .25rem;
            min-width: 0;
            width: 100%;
            overflow: hidden;
            padding: 1px 6px;
            line-height: 1.25;
            box-sizing: border-box;
            border-radius: 4px;
            background: #1a73e8;
            color: #fff;
        }
        #hb-calendar .hb-cal-event--interest {
            background: #e37400;
        }
        #hb-calendar .hb-cal-event__time { font-weight: 500; font-size: .68rem; flex: 0 0 auto; opacity: .95; }
        #hb-calendar .hb-cal-event__id { font-weight: 600; font-size: .68rem; flex: 0 0 auto; }
        #hb-calendar .hb-cal-event__job {
            flex: 1 1 0;
            min-width: 0;
            font-size: .68rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        #hb-calendar .fc-timegrid-col-events {
            display: block !important;
            flex-wrap: nowrap;
            justify-content: flex-start;
            gap: 0;
            margin: 0 !important;
            position: relative;
            min-height: 100%;
        }
        #hb-calendar .fc-timegrid-event-harness {
            position: absolute;
            left: 0;
            right: 0;
        }
        #hb-calendar .fc-timegrid-event {
            overflow: hidden;
            background: transparent !important;
            border: 0 !important;
            border-radius: 6px !important;
            min-height: 22px;
        }
        #hb-calendar .fc-timegrid-event .hb-cal-event {
            align-items: flex-start;
            height: 100%;
            min-height: 22px;
        }
        #hb-calendar .fc-timegrid-event .hb-cal-event__job {
            white-space: nowrap;
        }
        #hb-calendar .fc-timegrid .fc-scroller {
            overflow-y: auto !important;
        }
        #hb-calendar .fc-timeGridWeek-view .fc-view-harness,
        #hb-calendar .fc-timeGridDay-view .fc-view-harness {
            overflow: hidden !important;
        }
        #hb-calendar .fc-dayGridMonth-button::after,
        #hb-calendar .fc-timeGridWeek-button::after,
        #hb-calendar .fc-timeGridDay-button::after { content: none !important; display: none !important; }
        #hb-calendar .fc-toolbar-chunk .fc-button-group .fc-dayGridMonth-button,
        #hb-calendar .fc-toolbar-chunk .fc-button-group .fc-timeGridWeek-button,
        #hb-calendar .fc-toolbar-chunk .fc-button-group .fc-timeGridDay-button {
            width: auto;
            min-width: 3.75rem;
            height: 28px;
            padding: 2px 10px !important;
            font-size: .75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .hb-event-modal .modal-header { border-bottom: 1px solid #eceef3; }
        .hb-event-modal .modal-title { font-weight: 700; }
        .hb-event-dl { display: grid; grid-template-columns: 110px 1fr; gap: .55rem .75rem; margin: 0; }
        .hb-event-modal, .hb-day-modal { z-index: 2000; }
        .hb-event-dl dt { color: #6c757d; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; margin: 0; padding-top: .15rem; }
        .hb-event-dl dd { margin: 0; font-size: .9rem; color: #18181a; word-break: break-word; }
        .hb-event-job { font-weight: 600; }
        .hb-actions { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: flex-end; }
        .hb-actions .btn { white-space: nowrap; }
        .hb-bids-modal { z-index: 2000; }
        .hb-bids-modal .modal-dialog { max-width: 820px; }
        .hb-bids-modal .modal-header { padding: .7rem 1rem; }
        .hb-bids-modal .modal-body { padding: .75rem 1rem; }
        .hb-bids-modal .modal-footer { padding: .5rem 1rem; }
        .hb-response-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            align-items: stretch;
        }
        .hb-response-col {
            min-width: 0;
            border: 1px solid #e6e8ee;
            border-radius: 10px;
            padding: .65rem .7rem .7rem;
            background: #fff;
        }
        .hb-response-col--interested { border-color: #efd08a; }
        .hb-response-col--rejected { border-color: #f3c4c0; }
        .hb-response-col__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin: 0 0 .5rem;
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
        .hb-response-col__head--interested { color: #9a6700; }
        .hb-response-col__head--rejected { color: #b42318; }
        .hb-response-col--rejected .hb-provider-card { border-color: #f3c4c0; }
        .hb-remind {
            margin-top: .85rem;
            border: 1px solid #e6e8ee;
            border-radius: 10px;
            padding: .7rem .75rem;
            background: #f8f9fb;
        }
        .hb-remind__head {
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #25274D;
            margin: 0 0 .35rem;
        }
        .hb-remind__help { font-size: .75rem; color: #6c757d; margin: 0 0 .5rem; }
        .hb-remind__status {
            font-size: .8rem;
            margin: .55rem 0 0;
            padding: .45rem .55rem;
            border-radius: 8px;
        }
        .hb-remind__status--success {
            background: #e8f7ee;
            color: #146c2e;
            border: 1px solid #b7e4c7;
        }
        .hb-remind__status--error {
            background: #fdecec;
            color: #b42318;
            border: 1px solid #f3c4c0;
        }
        .hb-remind__status-msg,
        .hb-remind__status-wait { display: block; margin: 0; }
        .hb-remind__status-wait { margin-top: .2rem; }
        .hb-remind__wait-secs {
            display: inline-block;
            min-width: 2ch;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
            text-align: center;
        }
        .hb-remind #hb-remind-send .spinner-border {
            width: .85rem;
            height: .85rem;
            border-width: .15em;
            vertical-align: -0.1em;
        }
        .hb-provider-cards { display: flex; flex-direction: column; gap: .5rem; }
        .hb-provider-card {
            display: flex;
            flex-direction: column;
            gap: .4rem;
            border: 1px solid #e6e8ee;
            border-radius: 8px;
            padding: .5rem .65rem;
            background: #fff;
        }
        .hb-provider-card__row {
            display: flex;
            gap: .6rem;
            align-items: center;
            min-width: 0;
        }
        .hb-provider-card__img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex: 0 0 40px;
            background: #f3f4f6;
        }
        .hb-provider-card__meta { min-width: 0; flex: 1 1 auto; }
        .hb-provider-card__name {
            display: block;
            font-weight: 700;
            font-size: .85rem;
            color: #18181a;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .hb-provider-card__phone {
            display: block;
            margin-top: .1rem;
            font-size: .75rem;
            color: #6c757d;
            line-height: 1.2;
        }
        .hb-provider-card__msg {
            font-size: .75rem;
            color: #5f6368;
            line-height: 1.35;
            word-break: break-word;
        }
    </style>
    <div class="main-content hb-page {{ $viewMode === 'calendar' ? 'hb-page--calendar' : '' }}">
        <div class="container-fluid">
            <div class="page-title-wrap hb-page-head mb-3">
                <div class="hb-page-head__title">
                    <h2 class="page-title">{{ translate('Hunting_Board') }}</h2>
                    <p class="text-muted mb-0 small">{{ translate('Unassigned_work_help') }}</p>
                </div>
                <div class="hb-stat-row">
                    <div class="hb-stat">
                        <div class="hb-stat__label">{{ translate('Hunting_now') }}</div>
                        <div class="hb-stat__value">{{ $publishedCount }}</div>
                    </div>
                    <div class="hb-stat">
                        <div class="hb-stat__label">{{ translate('With_interest') }}</div>
                        <div class="hb-stat__value">{{ $withInterest }}</div>
                    </div>
                    <div class="hb-stat">
                        <div class="hb-stat__label">{{ translate('No_bids_yet') }}</div>
                        <div class="hb-stat__value">{{ $noInterest }}</div>
                    </div>
                </div>
                @php
                    $hbViewQuery = request()->except(['page', 'view']);
                @endphp
                <div class="btn-group hb-view-toggle" role="group" aria-label="{{ translate('Calendar') }}">
                    <a href="{{ route('admin.lead.hunting-board.index', array_merge($hbViewQuery, ['view' => 'list'])) }}"
                       class="btn btn-sm {{ $viewMode === 'list' ? 'btn--primary' : 'btn--secondary' }}">
                        <span class="material-icons">view_list</span>
                        {{ translate('List') }}
                    </a>
                    <a href="{{ route('admin.lead.hunting-board.index', array_merge($hbViewQuery, ['view' => 'calendar'])) }}"
                       class="btn btn-sm {{ $viewMode === 'calendar' ? 'btn--primary' : 'btn--secondary' }}">
                        <span class="material-icons">calendar_month</span>
                        {{ translate('Calendar') }}
                    </a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body {{ $viewMode === 'calendar' ? 'py-1 px-2' : 'py-2 px-3' }}">
                    <form method="GET" action="{{ route('admin.lead.hunting-board.index') }}">
                        <input type="hidden" name="view" value="{{ $viewMode }}">
                        <div class="hb-filters">
                            <div class="hb-filters__search">
                                <label class="form-label small mb-1" for="hb-search">{{ translate('Search') }}</label>
                                <input type="text" name="search" id="hb-search" class="form-control form-control-sm"
                                       value="{{ $search }}"
                                       placeholder="{{ translate('Search_by_name_phone_or_lead_id') }}">
                            </div>
                            <div class="hb-filters__field">
                                <label class="form-label small mb-1" for="hb-category">{{ translate('Category') }}</label>
                                <select name="category_id" id="hb-category" class="form-select form-select-sm js-select js-select-manual" data-placeholder="{{ translate('Select_Category') }}">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ $categoryId === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hb-filters__field">
                                <label class="form-label small mb-1" for="hb-subcategory">{{ translate('Sub_Category') }}</label>
                                <select name="sub_category_id" id="hb-subcategory" class="form-select form-select-sm js-select js-select-manual" data-placeholder="{{ translate('Select_Sub_Category') }}">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach($subCategories as $sub)
                                        <option value="{{ $sub->id }}"
                                                data-parent="{{ $sub->parent_id }}"
                                                {{ $subCategoryId === (string) $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hb-filters__field">
                                <label class="form-label small mb-1" for="hb-zone">{{ translate('Zone') }}</label>
                                <select name="zone_id" id="hb-zone" class="form-select form-select-sm js-select js-select-manual" data-placeholder="{{ translate('Select_Zone') }}">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}" {{ $zoneId === (string) $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="hb-filters__field">
                                <label class="form-label small mb-1" for="hb-bids">{{ translate('Interest') }}</label>
                                <select name="bids" id="hb-bids" class="form-select form-select-sm">
                                    <option value="all" {{ $bidFilter === 'all' ? 'selected' : '' }}>{{ translate('Any_bids') }}</option>
                                    <option value="has" {{ $bidFilter === 'has' ? 'selected' : '' }}>{{ translate('Has_interest') }}</option>
                                    <option value="none" {{ $bidFilter === 'none' ? 'selected' : '' }}>{{ translate('No_interest_yet') }}</option>
                                </select>
                            </div>
                            <div class="hb-filters__actions">
                                <span class="form-label small mb-1 invisible" aria-hidden="true">{{ translate('Search') }}</span>
                                <div class="hb-filters__buttons">
                                    <button class="btn btn--primary btn-sm" type="submit">{{ translate('Search') }}</button>
                                    <a class="btn btn--secondary btn-sm" href="{{ route('admin.lead.hunting-board.index', ['view' => $viewMode]) }}">{{ translate('Reset') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card hb-cal-card">
                <div class="card-body">
                    @if($viewMode === 'calendar')
                        <div class="custom-booking-calendar">
                            <div id="hb-calendar"></div>
                        </div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle hb-table">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Lead') }}</th>
                                <th>{{ translate('Job') }}</th>
                                <th>{{ translate('Area') }}</th>
                                <th>{{ translate('When') }}</th>
                                <th>{{ translate('Estimated_Service_Value') }}</th>
                                <th>{{ translate('Interest') }}</th>
                                <th>{{ translate('Posted') }}</th>
                                <th class="text-end">{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rows as $row)
                                @php $lead = $row['lead']; $public = $row['public']; @endphp
                                <tr>
                                    <td>
                                        <a class="hb-lead-id" href="{{ route('admin.lead.show', $lead->id) }}">#{{ $lead->id }}</a>
                                        <span class="hb-sub">{{ $lead->name }} · {{ $lead->phone_number }}</span>
                                    </td>
                                    <td>
                                        <span class="hb-job">{{ $public['service_name'] !== '—' ? $public['service_name'] : $public['subcategory_name'] }}</span>
                                        <span class="hb-sub">{{ $public['category_name'] }} · {{ $public['subcategory_name'] }}</span>
                                    </td>
                                    <td>{{ $public['area_name'] }} <span class="hb-sub">{{ $public['zone_name'] }}</span></td>
                                    <td>
                                        @if($public['estimated_at'])
                                            {{ $public['estimated_at']->format('d M Y, h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($public['estimated_value'] !== null)
                                            {{ with_currency_symbol($public['estimated_value']) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $row['interest_count'] }} {{ translate('Interested') }}</span>
                                        <span class="badge bg-danger mt-1">{{ $row['reject_count'] ?? 0 }} {{ translate('Rejected') }}</span>
                                    </td>
                                    <td>
                                        {{ $lead->hunting_started_at?->format('d M Y, h:i A') ?? '—' }}
                                        <span class="hb-sub">{{ $row['started_by'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="hb-actions">
                                            <button type="button"
                                                    class="btn btn--primary btn-sm hb-view-bids"
                                                    data-lead-id="{{ $lead->id }}"
                                                    data-pending-count="{{ (int) ($row['pending_count'] ?? 0) }}"
                                                    data-remind-url="{{ $row['remind_url'] ?? '' }}">
                                                {{ translate('View_responses') }}
                                            </button>
                                            <template id="hb-interest-tpl-{{ $lead->id }}">
                                                @foreach($row['interests'] as $interest)
                                                    <article class="hb-provider-card">
                                                        <div class="hb-provider-card__row">
                                                            <img class="hb-provider-card__img"
                                                                 src="{{ $interest['image'] }}"
                                                                 alt="{{ $interest['name'] }}"
                                                                 onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                                            <div class="hb-provider-card__meta">
                                                                @if(!empty($interest['url']))
                                                                    <a class="hb-provider-card__name" href="{{ $interest['url'] }}">{{ $interest['name'] }}</a>
                                                                @else
                                                                    <span class="hb-provider-card__name">{{ $interest['name'] }}</span>
                                                                @endif
                                                                <span class="hb-provider-card__phone">{{ $interest['phone'] !== '' ? $interest['phone'] : '—' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="hb-provider-card__msg">{{ $interest['note'] ?: '—' }}</div>
                                                    </article>
                                                @endforeach
                                            </template>
                                            <template id="hb-reject-tpl-{{ $lead->id }}">
                                                @foreach($row['rejections'] ?? [] as $rejection)
                                                    <article class="hb-provider-card">
                                                        <div class="hb-provider-card__row">
                                                            <img class="hb-provider-card__img"
                                                                 src="{{ $rejection['image'] }}"
                                                                 alt="{{ $rejection['name'] }}"
                                                                 onerror="this.onerror=null;this.src='{{ asset('assets/provider-module/img/user2x.png') }}'">
                                                            <div class="hb-provider-card__meta">
                                                                @if(!empty($rejection['url']))
                                                                    <a class="hb-provider-card__name" href="{{ $rejection['url'] }}">{{ $rejection['name'] }}</a>
                                                                @else
                                                                    <span class="hb-provider-card__name">{{ $rejection['name'] }}</span>
                                                                @endif
                                                                <span class="hb-provider-card__phone">{{ $rejection['phone'] !== '' ? $rejection['phone'] : '—' }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="hb-provider-card__msg">{{ $rejection['note'] ?: '—' }}</div>
                                                    </article>
                                                @endforeach
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">{{ translate('No_leads_on_hunting_board') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($leads && $leads->hasPages())
                        <div class="mt-3">{{ $leads->links() }}</div>
                    @endif
                    @endif
                    @if($viewMode !== 'calendar')
                    <p class="text-muted small mb-0 mt-2">{{ translate('Unassigned_work_staff_privacy_note') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($viewMode !== 'calendar')
        <div class="modal fade hb-bids-modal" id="hbBidsModal" tabindex="-1" aria-labelledby="hbBidsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hbBidsModalLabel">{{ translate('View_responses') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div id="hb-bids-list"></div>
                        @can('lead_update')
                            <div class="hb-remind" id="hb-remind-box">
                                <p class="hb-remind__head">{{ translate('Send_reminder') }}</p>
                                <p class="hb-remind__help" id="hb-remind-help"></p>
                                <label class="form-label small mb-1" for="hb-remind-message">{{ translate('Reminder_message') }}</label>
                                <textarea id="hb-remind-message" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
                                <button type="button" class="btn btn--primary btn-sm mt-2" id="hb-remind-send">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" id="hb-remind-spinner" role="status" aria-hidden="true"></span>
                                    <span id="hb-remind-send-label">{{ translate('Send_reminder') }}</span>
                                </button>
                                <div class="hb-remind__status d-none" id="hb-remind-status">
                                    <p class="hb-remind__status-msg mb-0" id="hb-remind-status-msg"></p>
                                    @php
                                        $hbWaitParts = explode(':seconds', translate('Reminder_wait_before_resend'), 2);
                                    @endphp
                                    <p class="hb-remind__status-wait mb-0 d-none" id="hb-remind-status-wait" aria-hidden="true">{{ $hbWaitParts[0] ?? '' }}<span class="hb-remind__wait-secs" id="hb-remind-wait-secs">60</span>{{ $hbWaitParts[1] ?? '' }}</p>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($viewMode === 'calendar')
        <div class="modal fade hb-event-modal" id="hbEventModal" tabindex="-1" aria-labelledby="hbEventModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hbEventModalLabel">{{ translate('Lead') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="hb-event-job mb-3" id="hb-event-job"></p>
                        <dl class="hb-event-dl">
                            <dt>{{ translate('When') }}</dt>
                            <dd id="hb-event-when"></dd>
                            <dt>{{ translate('Estimated_Service_Value') }}</dt>
                            <dd id="hb-event-value"></dd>
                            <dt>{{ translate('Area') }}</dt>
                            <dd id="hb-event-area"></dd>
                            <dt>{{ translate('Category') }}</dt>
                            <dd id="hb-event-category"></dd>
                            <dt>{{ translate('Customer') }}</dt>
                            <dd id="hb-event-customer"></dd>
                            <dt>{{ translate('Interest') }}</dt>
                            <dd id="hb-event-bids"></dd>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                        <a href="#" id="hb-event-open" class="btn btn--primary">{{ translate('Go_to_lead') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade hb-day-modal" id="hbDayModal" tabindex="-1" aria-labelledby="hbDayModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="hbDayModalLabel">{{ translate('Jobs_for_this_day') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body" id="hb-day-jobs"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@php
    $hbSubCategoriesJson = $subCategories->map(function ($s) {
        return [
            'id' => (string) $s->id,
            'name' => $s->name,
            'parent_id' => (string) ($s->parent_id ?? ''),
        ];
    })->values();
@endphp

@push('script')
@if($viewMode === 'calendar')
<script src="{{ asset('assets/admin-module/js/fullcalendar.js') }}"></script>
@endif
<script>
    (function () {
        var allSubs = @json($hbSubCategoriesJson);
        var selectedSub = @json((string) $subCategoryId);
        var allLabel = @json(translate('All'));

        function refreshSelect2($el) {
            if (!$el.length || !$.fn.select2) return;
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                width: '100%',
                placeholder: $el.data('placeholder') || allLabel,
                allowClear: true,
                minimumResultsForSearch: 0
            });
        }

        function fillSubcategories(categoryId, keepSelected) {
            var $sub = $('#hb-subcategory');
            var current = keepSelected ? (keepSelected === true ? ($sub.val() || selectedSub) : keepSelected) : '';
            $sub.empty().append(new Option(allLabel, '', false, current === ''));
            allSubs.forEach(function (sub) {
                if (categoryId && String(sub.parent_id) !== String(categoryId)) {
                    return;
                }
                $sub.append(new Option(sub.name, sub.id, false, String(sub.id) === String(current)));
            });
            refreshSelect2($sub);
        }

        $(function () {
            var $cat = $('#hb-category');
            refreshSelect2($cat);
            refreshSelect2($('#hb-zone'));
            fillSubcategories($cat.val() || '', true);
            $cat.on('change', function () {
                fillSubcategories($(this).val() || '', false);
            });

            var bidsModalEl = document.getElementById('hbBidsModal');
            if (bidsModalEl) {
                if (bidsModalEl.parentNode !== document.body) {
                    document.body.appendChild(bidsModalEl);
                }
                var labels = {
                    provider: @json(translate('Provider')),
                    empty: @json(translate('No_interest_yet')),
                    emptyRejects: @json(translate('No_rejections_yet')),
                    interestedHeading: @json(translate('Interested')),
                    rejectedHeading: @json(translate('Rejected')),
                    title: @json(translate('View_responses')),
                    leadPrefix: @json(translate('Lead')),
                    remindHelp: @json(translate('Open_request_reminder_help')),
                    remindNone: @json(translate('Open_request_reminder_none')),
                    remindDefault: @json(translate('Default_open_request_reminder')),
                    remindSend: @json(translate('Send_reminder')),
                    remindSending: @json(translate('Sending...')),
                    remindWait: @json(translate('Reminder_wait_before_resend')),
                    remindMessageRequired: @json(translate('Reminder_message_required'))
                };
                var remindCooldowns = {};
                var remindCountdownTimer = null;
                var remindSending = false;
                var remindWaitShownFor = '';

                function remindEls() {
                    return {
                        box: document.getElementById('hb-remind-box'),
                        help: document.getElementById('hb-remind-help'),
                        message: document.getElementById('hb-remind-message'),
                        send: document.getElementById('hb-remind-send'),
                        spinner: document.getElementById('hb-remind-spinner'),
                        label: document.getElementById('hb-remind-send-label'),
                        status: document.getElementById('hb-remind-status'),
                        statusMsg: document.getElementById('hb-remind-status-msg'),
                        statusWait: document.getElementById('hb-remind-status-wait'),
                        waitSecs: document.getElementById('hb-remind-wait-secs')
                    };
                }

                function remindUrl(box) {
                    return box ? (box.getAttribute('data-url') || '') : '';
                }

                function remindPending(box) {
                    return box ? (parseInt(box.getAttribute('data-pending'), 10) || 0) : 0;
                }

                function hideRemindStatus() {
                    var els = remindEls();
                    remindWaitShownFor = '';
                    if (els.status) {
                        els.status.classList.add('d-none');
                        els.status.classList.remove('hb-remind__status--success', 'hb-remind__status--error');
                    }
                    if (els.statusMsg) els.statusMsg.textContent = '';
                    if (els.statusWait) els.statusWait.classList.add('d-none');
                }

                function showRemindError(text) {
                    var els = remindEls();
                    if (!els.status) return;
                    stopRemindCountdown();
                    remindWaitShownFor = '';
                    els.status.classList.remove('d-none', 'hb-remind__status--success');
                    els.status.classList.add('hb-remind__status--error');
                    if (els.statusMsg) els.statusMsg.textContent = text || '';
                    if (els.statusWait) els.statusWait.classList.add('d-none');
                }

                function showRemindSuccess(text) {
                    var els = remindEls();
                    if (!els.status) return;
                    els.status.classList.remove('d-none', 'hb-remind__status--error');
                    els.status.classList.add('hb-remind__status--success');
                    if (els.statusMsg && els.statusMsg.textContent !== (text || '')) {
                        els.statusMsg.textContent = text || '';
                    }
                }

                function setWaitSeconds(secs) {
                    var secsEl = document.getElementById('hb-remind-wait-secs');
                    if (!secsEl) return;
                    var next = String(Math.max(0, secs));
                    if (secsEl.firstChild) {
                        if (secsEl.firstChild.nodeValue !== next) {
                            secsEl.firstChild.nodeValue = next;
                        }
                    } else {
                        secsEl.appendChild(document.createTextNode(next));
                    }
                }

                function setRemindLoading(on) {
                    var els = remindEls();
                    if (els.spinner) els.spinner.classList.toggle('d-none', !on);
                    if (els.label) els.label.textContent = on ? labels.remindSending : labels.remindSend;
                    if (els.send) els.send.setAttribute('aria-busy', on ? 'true' : 'false');
                    if (els.message) els.message.disabled = on || remindPending(els.box) < 1;
                }

                function cooldownRemaining(url) {
                    var until = remindCooldowns[url] ? remindCooldowns[url].until : 0;
                    return Math.max(0, until - Date.now());
                }

                function stopRemindCountdown() {
                    if (remindCountdownTimer) {
                        clearInterval(remindCountdownTimer);
                        remindCountdownTimer = null;
                    }
                }

                function tickRemindWait() {
                    var els = remindEls();
                    var url = remindUrl(els.box);
                    var remaining = cooldownRemaining(url);
                    if (remaining <= 0) {
                        stopRemindCountdown();
                        if (els.statusWait) els.statusWait.classList.add('d-none');
                        remindWaitShownFor = '';
                        if (els.send && remindPending(els.box) > 0) {
                            els.send.disabled = false;
                        }
                        return;
                    }
                    setWaitSeconds(Math.ceil(remaining / 1000));
                }

                function startRemindCountdown() {
                    var els = remindEls();
                    var url = remindUrl(els.box);
                    var remaining = cooldownRemaining(url);
                    if (remaining <= 0) {
                        tickRemindWait();
                        return;
                    }
                    if (els.send) els.send.disabled = true;
                    showRemindSuccess(remindCooldowns[url] ? (remindCooldowns[url].message || '') : '');
                    if (els.statusWait) els.statusWait.classList.remove('d-none');
                    if (remindWaitShownFor !== url) {
                        setWaitSeconds(Math.ceil(remaining / 1000));
                        remindWaitShownFor = url;
                    }
                    if (!remindCountdownTimer) {
                        remindCountdownTimer = setInterval(tickRemindWait, 1000);
                    }
                }

                function buildResponseColumn(tplId, headingText, emptyText, tone) {
                    var col = document.createElement('div');
                    col.className = 'hb-response-col hb-response-col--' + tone;
                    var tpl = document.getElementById(tplId);
                    var items = tpl ? tpl.content.cloneNode(true) : null;
                    var count = items && items.querySelectorAll ? items.querySelectorAll('.hb-provider-card').length : 0;
                    var heading = document.createElement('h6');
                    heading.className = 'hb-response-col__head hb-response-col__head--' + tone;
                    heading.textContent = headingText + ' (' + count + ')';
                    col.appendChild(heading);
                    if (!count) {
                        var empty = document.createElement('p');
                        empty.className = 'text-muted mb-0';
                        empty.textContent = emptyText;
                        col.appendChild(empty);
                    } else {
                        var wrap = document.createElement('div');
                        wrap.className = 'hb-provider-cards';
                        wrap.appendChild(items);
                        col.appendChild(wrap);
                    }
                    return col;
                }

                function showBidsModal() {
                    if (window.bootstrap && bootstrap.Modal) {
                        try {
                            var prev = bootstrap.Modal.getInstance(bidsModalEl);
                            if (prev) prev.dispose();
                        } catch (e) {}
                        bootstrap.Modal.getOrCreateInstance(bidsModalEl, { backdrop: true, keyboard: true }).show();
                        return;
                    }
                    if (window.jQuery && jQuery.fn.modal) {
                        jQuery(bidsModalEl).modal('show');
                    }
                }

                document.querySelectorAll('.hb-view-bids').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var leadId = btn.getAttribute('data-lead-id') || '';
                        var title = document.getElementById('hbBidsModalLabel');
                        if (title) title.textContent = labels.title + ' · ' + labels.leadPrefix + ' #' + leadId;
                        var list = document.getElementById('hb-bids-list');
                        if (!list) return;
                        list.innerHTML = '';
                        var grid = document.createElement('div');
                        grid.className = 'hb-response-grid';
                        grid.appendChild(buildResponseColumn('hb-interest-tpl-' + leadId, labels.interestedHeading, labels.empty, 'interested'));
                        grid.appendChild(buildResponseColumn('hb-reject-tpl-' + leadId, labels.rejectedHeading, labels.emptyRejects, 'rejected'));
                        list.appendChild(grid);
                        var remindBox = document.getElementById('hb-remind-box');
                        var remindHelp = document.getElementById('hb-remind-help');
                        var remindMessage = document.getElementById('hb-remind-message');
                        var remindSend = document.getElementById('hb-remind-send');
                        var pending = parseInt(btn.getAttribute('data-pending-count'), 10) || 0;
                        if (remindBox) {
                            remindBox.setAttribute('data-url', btn.getAttribute('data-remind-url') || '');
                            remindBox.setAttribute('data-pending', String(pending));
                        }
                        if (remindHelp) {
                            remindHelp.textContent = pending > 0
                                ? labels.remindHelp.replace(':count', String(pending))
                                : labels.remindNone;
                        }
                        if (remindMessage) {
                            remindMessage.value = labels.remindDefault;
                            remindMessage.disabled = pending < 1 || remindSending;
                        }
                        if (remindSend) {
                            remindSend.disabled = pending < 1 || cooldownRemaining(remindUrl(remindBox)) > 0 || remindSending;
                        }
                        if (!remindSending) {
                            setRemindLoading(false);
                            if (cooldownRemaining(remindUrl(remindBox)) > 0) {
                                startRemindCountdown();
                            } else {
                                stopRemindCountdown();
                                hideRemindStatus();
                            }
                        }
                        showBidsModal();
                    });
                });

                var remindSendBtn = document.getElementById('hb-remind-send');
                if (remindSendBtn) {
                    remindSendBtn.addEventListener('click', function () {
                        var els = remindEls();
                        var url = remindUrl(els.box);
                        var pending = remindPending(els.box);
                        var message = els.message ? String(els.message.value || '').trim() : '';
                        if (!url || pending < 1 || remindSending || cooldownRemaining(url) > 0) {
                            return;
                        }
                        if (message.length < 3) {
                            showRemindError(labels.remindMessageRequired);
                            return;
                        }

                        remindSending = true;
                        remindSendBtn.disabled = true;
                        setRemindLoading(true);
                        hideRemindStatus();

                        var csrf = document.querySelector('meta[name="csrf-token"]');
                        var token = csrf ? csrf.getAttribute('content') : '';
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ message: message })
                        }).then(function (res) {
                            return res.json().then(function (data) {
                                return { ok: res.ok, status: res.status, data: data };
                            }).catch(function () {
                                return { ok: res.ok, status: res.status, data: {} };
                            });
                        }).then(function (result) {
                            var text = (result.data && result.data.message) ? result.data.message : '';
                            var retryAfter = parseInt(result.data && result.data.retry_after, 10) || 0;
                            if (result.ok && result.data && result.data.success) {
                                remindCooldowns[url] = {
                                    until: Date.now() + ((retryAfter > 0 ? retryAfter : 60) * 1000),
                                    message: text
                                };
                                remindSending = false;
                                setRemindLoading(false);
                                remindSendBtn.disabled = true;
                                startRemindCountdown();
                                return;
                            }
                            remindSending = false;
                            setRemindLoading(false);
                            if (result.status === 429 && retryAfter > 0) {
                                remindCooldowns[url] = { until: Date.now() + (retryAfter * 1000), message: '' };
                                startRemindCountdown();
                                return;
                            }
                            showRemindError(text || labels.remindNone);
                            remindSendBtn.disabled = pending < 1;
                        }).catch(function () {
                            remindSending = false;
                            setRemindLoading(false);
                            showRemindError(labels.remindNone);
                            remindSendBtn.disabled = pending < 1 || cooldownRemaining(url) > 0;
                        });
                    });
                }
            }

            var calendarEl = document.getElementById('hb-calendar');
            if (!calendarEl) {
                return;
            }

            function withFullCalendar(done) {
                if (window.FullCalendar) {
                    done();
                    return;
                }
                if (!document.querySelector('script[src*="fullcalendar.js"]')) {
                    var script = document.createElement('script');
                    script.src = @json(asset('assets/admin-module/js/fullcalendar.js'));
                    document.head.appendChild(script);
                }
                var waited = 0;
                var timer = window.setInterval(function () {
                    waited += 50;
                    if (window.FullCalendar || waited >= 4000) {
                        window.clearInterval(timer);
                        done();
                    }
                }, 50);
            }

            withFullCalendar(function () {
            if (calendarEl && window.FullCalendar) {
                var events = @json($calendarEvents);
                var modalEl = document.getElementById('hbEventModal');
                var dayModalEl = document.getElementById('hbDayModal');
                document.querySelectorAll('body > .hb-event-modal, body > .hb-day-modal').forEach(function (old) {
                    if (old !== modalEl && old !== dayModalEl) {
                        old.remove();
                    }
                });
                var dash = '—';
                var noJobsLabel = @json(translate('No_jobs_on_this_day'));
                var jobsForDayLabel = @json(translate('Jobs_for_this_day'));

                if (modalEl && modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
                if (dayModalEl && dayModalEl.parentNode !== document.body) {
                    document.body.appendChild(dayModalEl);
                }

                function setText(id, value) {
                    var node = document.getElementById(id);
                    if (node) node.textContent = value || dash;
                }

                var ignoreDateClickUntil = 0;
                var calendar;
                var eventsById = {};

                function eventFromClickTarget(target) {
                    if (!target || !target.closest) return null;
                    var node = target.closest('[data-hb-event-id]');
                    if (!node) {
                        var hit = target.closest('.fc-event, .hb-cal-event');
                        node = hit ? hit.closest('[data-hb-event-id]') || hit : null;
                    }
                    if (!node) return null;
                    var id = node.getAttribute('data-hb-event-id');
                    if (!id) return null;
                    if (eventsById[id]) return eventsById[id];
                    return calendar ? calendar.getEventById(id) : null;
                }

                function showHbModal(el) {
                    if (!el) return;
                    document.body.appendChild(el);
                    if (window.bootstrap && bootstrap.Modal) {
                        try {
                            var prev = bootstrap.Modal.getInstance(el);
                            if (prev) prev.dispose();
                        } catch (e) {}
                        bootstrap.Modal.getOrCreateInstance(el, { backdrop: true, keyboard: true }).show();
                        return;
                    }
                    if (window.jQuery && jQuery.fn.modal) {
                        jQuery(el).modal('show');
                    }
                }

                function hideHbModal(el) {
                    if (!el) return;
                    if (window.jQuery && jQuery.fn.modal) {
                        jQuery(el).modal('hide');
                        return;
                    }
                    if (window.bootstrap && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(el);
                        if (inst) inst.hide();
                    }
                }

                function dayKey(value) {
                    var d = value instanceof Date ? value : new Date(value);
                    if (isNaN(d.getTime())) {
                        return String(value || '').slice(0, 10);
                    }
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                function formatDayTitle(date) {
                    try {
                        return date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                    } catch (e) {
                        return dayKey(date);
                    }
                }

                function openEventModal(event) {
                    if (!event) return;
                    ignoreDateClickUntil = Date.now() + 400;
                    var p = event.extendedProps || {};
                    var title = document.getElementById('hbEventModalLabel');
                    if (title) title.textContent = '{{ translate('Lead') }} #' + (p.leadId || event.id);
                    setText('hb-event-job', p.job);
                    setText('hb-event-when', p.when);
                    setText('hb-event-value', p.value || '—');
                    setText('hb-event-area', [p.area, p.zone].filter(Boolean).join(' · '));
                    setText('hb-event-category', [p.category, p.subcategory].filter(Boolean).join(' · '));
                    var customer = [p.customer, p.phone].filter(Boolean).join(' · ');
                    setText('hb-event-customer', customer);
                    var bids = parseInt(p.bids, 10) || 0;
                    var rejects = parseInt(p.rejects, 10) || 0;
                    var bidParts = [];
                    bidParts.push(bids + ' {{ translate('Interested') }}');
                    bidParts.push(rejects + ' {{ translate('Rejected') }}');
                    setText('hb-event-bids', bidParts.join(' · '));
                    var openBtn = document.getElementById('hb-event-open');
                    if (openBtn) {
                        openBtn.setAttribute('href', p.url || '#');
                        openBtn.setAttribute('data-turbo', 'false');
                    }
                    var dayOpen = dayModalEl && dayModalEl.classList.contains('show');
                    if (dayOpen) {
                        jQuery(dayModalEl).one('hidden.bs.modal', function () {
                            showHbModal(modalEl);
                        });
                        hideHbModal(dayModalEl);
                        return;
                    }
                    showHbModal(modalEl);
                }

                function openDayModal(date) {
                    var key = dayKey(date);
                    var title = document.getElementById('hbDayModalLabel');
                    if (title) title.textContent = jobsForDayLabel + ' · ' + formatDayTitle(date);
                    var list = document.getElementById('hb-day-jobs');
                    if (!list) return;
                    var dayEvents = calendar.getEvents().filter(function (ev) {
                        return ev.start && dayKey(ev.start) === key;
                    }).sort(function (a, b) {
                        return (a.start ? a.start.getTime() : 0) - (b.start ? b.start.getTime() : 0);
                    });
                    list.innerHTML = '';
                    if (!dayEvents.length) {
                        var empty = document.createElement('p');
                        empty.className = 'text-muted mb-0';
                        empty.textContent = noJobsLabel;
                        list.appendChild(empty);
                    } else {
                        dayEvents.forEach(function (ev) {
                            var p = ev.extendedProps || {};
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'hb-day-job';
                            btn.innerHTML = '<span class="hb-day-job__top"><span class="hb-day-job__id">#' + (p.leadId || ev.id) + '</span><span class="hb-day-job__time"></span></span><span class="hb-day-job__job"></span><span class="hb-day-job__meta"></span>';
                            btn.querySelector('.hb-day-job__time').textContent = p.when || '';
                            btn.querySelector('.hb-day-job__job').textContent = p.job || '';
                            var meta = [p.customer, p.area, p.zone].filter(Boolean).join(' · ');
                            var bids = parseInt(p.bids, 10) || 0;
                            btn.querySelector('.hb-day-job__meta').textContent = meta + (bids ? (' · ' + bids + ' {{ translate('Interested') }}') : '');
                            btn.addEventListener('click', function () {
                                openEventModal(ev);
                            });
                            list.appendChild(btn);
                        });
                    }
                    showHbModal(dayModalEl);
                }

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    firstDay: 1,
                    height: 'parent',
                    expandRows: false,
                    nowIndicator: true,
                    dayMaxEvents: 3,
                    displayEventEnd: false,
                    eventDisplay: 'block',
                    defaultTimedEventDuration: '01:00:00',
                    forceEventDuration: true,
                    scrollTime: '08:00:00',
                    slotMinTime: '00:00:00',
                    slotMaxTime: '24:00:00',
                    eventInteractive: true,
                    navLinks: false,
                    selectable: false,
                    allDaySlot: false,
                    headerToolbar: {
                        left: 'dayGridMonth,timeGridWeek,timeGridDay',
                        center: 'title',
                        right: 'prev,next today'
                    },
                    buttonText: {
                        today: '{{ translate('Today') }}',
                        month: '{{ translate('Month') }}',
                        week: '{{ translate('Week') }}',
                        day: '{{ translate('Day') }}'
                    },
                    events: events,
                    datesSet: function () {
                        window.requestAnimationFrame(fitHuntingCalendar);
                    },
                    eventTimeFormat: { hour: 'numeric', minute: '2-digit', hour12: true },
                    eventContent: function (arg) {
                        var p = arg.event.extendedProps || {};
                        var wrap = document.createElement('div');
                        wrap.className = 'hb-cal-event' + ((parseInt(p.bids, 10) || 0) > 0 ? ' hb-cal-event--interest' : '');
                        wrap.setAttribute('data-hb-event-id', String(arg.event.id));
                        var time = document.createElement('span');
                        time.className = 'hb-cal-event__time';
                        time.textContent = arg.timeText || '';
                        var id = document.createElement('span');
                        id.className = 'hb-cal-event__id';
                        id.textContent = '#' + (p.leadId || arg.event.id);
                        var job = document.createElement('span');
                        job.className = 'hb-cal-event__job';
                        job.textContent = p.jobShort || p.job || '';
                        wrap.appendChild(time);
                        wrap.appendChild(id);
                        wrap.appendChild(job);
                        return { domNodes: [wrap] };
                    },
                    eventDidMount: function (info) {
                        var id = String(info.event.id);
                        eventsById[id] = info.event;
                        info.el.setAttribute('data-hb-event-id', id);
                        info.el.setAttribute('data-turbo', 'false');
                        info.el.removeAttribute('href');
                        info.el.style.cursor = 'pointer';
                        info.el.style.pointerEvents = 'auto';
                        info.el.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            openEventModal(info.event);
                        }, true);
                    },
                    eventClick: function (info) {
                        info.jsEvent.preventDefault();
                        info.jsEvent.stopPropagation();
                        openEventModal(info.event);
                    },
                    dateClick: function (info) {
                        if (Date.now() < ignoreDateClickUntil) {
                            return;
                        }
                        var jsEvent = info.jsEvent;
                        var fromTarget = eventFromClickTarget(jsEvent && jsEvent.target);
                        if (fromTarget) {
                            openEventModal(fromTarget);
                            return;
                        }
                        if (jsEvent) {
                            var under = document.elementFromPoint(jsEvent.clientX, jsEvent.clientY);
                            var fromPoint = eventFromClickTarget(under);
                            if (fromPoint) {
                                openEventModal(fromPoint);
                                return;
                            }
                        }
                        openDayModal(info.date);
                    },
                    moreLinkClick: function (info) {
                        openDayModal(info.date);
                        return 'none';
                    }
                });
                function fitHuntingCalendar() {
                    var footer = document.querySelector('footer.footer');
                    var footerTop = footer ? footer.getBoundingClientRect().top : window.innerHeight;
                    var top = calendarEl.getBoundingClientRect().top;
                    var available = Math.max(280, Math.floor(footerTop - top - 8));
                    var view = calendar.view && calendar.view.type ? calendar.view.type : '';
                    var h = available;
                    if (view === 'dayGridMonth') {
                        var weeks = calendarEl.querySelectorAll('.fc-daygrid-body tr').length || 6;
                        h = Math.min(available, 32 + (weeks * 96));
                    }
                    calendarEl.style.height = h + 'px';
                    calendar.setOption('height', h);
                    calendar.updateSize();
                }

                calendar.render();
                calendarEl.addEventListener('click', function (e) {
                    var ev = eventFromClickTarget(e.target);
                    if (!ev) return;
                    e.preventDefault();
                    e.stopPropagation();
                    openEventModal(ev);
                }, true);
                fitHuntingCalendar();
                window.addEventListener('resize', fitHuntingCalendar);
                window.requestAnimationFrame(fitHuntingCalendar);
                window.setTimeout(fitHuntingCalendar, 50);
                window.setTimeout(fitHuntingCalendar, 250);
            }
            });
        });
    })();
</script>
@endpush

