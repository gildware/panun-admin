@extends('adminmodule::layouts.new-master')

@section('title', translate('dashboard'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-dashboard.css') }}?v=2026101512">
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=2026101512">
<style>
    .staff-presence-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        border-top: 2px solid #43466e;
        overflow: hidden;
    }
    .staff-presence-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding: 8px 12px;
        background: #eef0f6;
        border-bottom: 1px solid #e5e7eb;
    }
    .staff-presence-panel__title {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        font-size: 12px;
        font-weight: 700;
        color: #363856;
        line-height: 1.2;
    }
    .staff-presence-panel__title .material-symbols-outlined {
        font-size: 16px;
        color: #43466e;
    }
    .staff-presence-panel__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #d7dae8;
        color: #43466e;
        font-size: 10px;
        font-weight: 700;
    }
    .staff-presence-panel__tools {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        min-width: 0;
    }
    .staff-presence-summary {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }
    .staff-presence-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }
    .staff-presence-chip strong { font-weight: 700; }
    .staff-presence-chip--online { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
    .staff-presence-chip--busy { background: #fffbeb; border-color: #fde68a; color: #b45309; }
    .staff-presence-chip--break { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
    .staff-presence-chip--offline { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
    .staff-presence-history-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        background: #fff;
        color: #475569;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
    }
    .staff-presence-history-btn:hover {
        background: #f8fafc;
        color: #111827;
    }
    .staff-presence-history-btn .material-symbols-outlined { font-size: 14px; }
    .staff-presence-panel__body { padding: 0; }
    .staff-presence-table-wrap {
        max-height: 340px;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .staff-presence-table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
        font-size: 11px;
        line-height: 1.25;
        white-space: nowrap;
    }
    .staff-presence-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        padding: 6px 10px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.01em;
    }
    .staff-presence-table tbody td {
        padding: 5px 10px;
        border-bottom: 1px solid #f1f5f9;
        color: #64748b;
        vertical-align: middle;
    }
    .staff-presence-table tbody tr:last-child td { border-bottom: 0; }
    .staff-presence-table tbody tr:hover td { background: #f8fafc; }
    .staff-presence-employee-col {
        min-width: 180px;
        width: 180px;
    }
    .staff-presence-person {
        display: flex;
        align-items: center;
        gap: 7px;
        min-width: 0;
    }
    .staff-presence-avatar-wrap {
        position: relative;
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }
    .staff-presence-avatar {
        width: 28px !important;
        height: 28px !important;
        min-width: 28px;
        min-height: 28px;
        border-radius: 999px;
        object-fit: cover;
        display: block;
    }
    .staff-presence-dot {
        position: absolute;
        right: -1px;
        bottom: -1px;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        border: 1.5px solid #fff;
    }
    .staff-presence-person__meta { min-width: 0; }
    .staff-presence-person__name {
        color: #1f2937;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .staff-presence-person__email {
        color: #94a3b8;
        font-size: 10px;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 140px;
    }
    .staff-presence-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 10px;
        font-weight: 700;
        line-height: 1.2;
        color: inherit;
    }
    .staff-presence-badge--online,
    .staff-presence-badge.bg-success {
        background: #ecfdf5 !important;
        border-color: #86efac;
        color: #047857 !important;
    }
    .staff-presence-badge--busy,
    .staff-presence-badge.bg-warning {
        background: #fffbeb !important;
        border-color: #fcd34d;
        color: #b45309 !important;
    }
    .staff-presence-badge--break,
    .staff-presence-badge.bg-info {
        background: #ecfeff !important;
        border-color: #67e8f9;
        color: #0e7490 !important;
    }
    .staff-presence-badge--offline,
    .staff-presence-badge.bg-secondary {
        background: #f1f5f9 !important;
        border-color: #cbd5e1;
        color: #475569 !important;
    }
    .staff-presence-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
        color: #94a3b8;
        font-size: 12px;
    }
    #staffPresenceHistoryModal .staff-presence-table thead th {
        position: static;
    }
    .staff-presence-history-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .staff-presence-history-header__actions {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-left: auto;
        flex-shrink: 0;
    }
    .staff-presence-history-date {
        width: auto;
        min-width: 9.5rem;
        max-width: 12rem;
        height: 1.85rem;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        padding: 0.15rem 0.45rem;
        line-height: 1.2;
        cursor: pointer;
    }
    .staff-presence-history-date::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.75;
    }
</style>
@endpush

@section('content')
@php
    $monthly = $employeeData['monthly'] ?? [];
    $todayDone = $employeeData['today_done'] ?? [];
    $workQueue = $employeeData['work_queue'] ?? [];
    $dashboardEmployees = $employeeData['dashboard_employees'] ?? [];
    $defaultEmployeeId = $employeeData['default_employee_id'] ?? '';
    $defaultDashboardScope = $employeeData['default_dashboard_scope'] ?? '__all__';
@endphp

<div class="main-content emp-dash {{ is_admin_employee() ? 'emp-dash--employee' : 'emp-dash--admin' }}">
    <div class="container-fluid">
        @if(! is_admin_employee())
            <div class="emp-dash-topbar">
                @include('adminmodule::partials._admin-dashboard-switcher', ['active' => 'work'])
                @if($dashboardEmployees !== [])
                    <div class="emp-dash-employee-filter">
                        <label class="visually-hidden" for="dashboard-employee-select">{{ translate('Select_employee') }}</label>
                        <select id="dashboard-employee-select"
                                class="form-select form-select-sm js-dashboard-employee-select"
                                data-default-scope="{{ $defaultDashboardScope }}">
                            <option value="__all__" selected>{{ translate('All') }}</option>
                            @foreach($dashboardEmployees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif

        {{-- Work queue: pending vs new to pick up --}}
        <div id="what-needs-attention" class="mb-3">
            <div class="work-queue-split">
                @foreach($workQueue as $laneKey => $lane)
                    <div class="work-queue-lane work-queue-lane--{{ $laneKey }}" id="work-queue-{{ $laneKey }}">
                        <div class="lane-header">
                            <h5 class="lane-title">{{ $lane['title'] ?? '' }}</h5>
                        </div>

                        <div class="lane-boxes-row" id="priority-inbox-{{ $laneKey }}">
                            @foreach($lane['boxes'] ?? [] as $box)
                                @if(! empty($box['requires_permission']) && ! Gate::check($box['requires_permission']))
                                    @continue
                                @endif
                                @include('adminmodule::partials._employee-work-queue-box', ['box' => $box])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @php
            $progressScopes = $employeeData['progress_scopes'] ?? [];
        @endphp

        @if($progressScopes !== [])
            <div id="section-progress"
                 class="js-progress-scope-wrapper"
                 data-scope-url="{{ route('admin.dashboard.progress-scope') }}">
                <div class="js-progress-scope-loading d-none text-center py-4" role="status">
                    <div class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></div>
                    <span class="ms-2">{{ translate('Loading') }}</span>
                </div>
                @foreach($progressScopes as $scopeId => $scope)
                    @include('adminmodule::partials._employee-progress-scope-panel', [
                        'scopeId' => $scopeId,
                        'scope' => $scope,
                        'dashboardEmployees' => $dashboardEmployees,
                        'hidden' => $scopeId !== '__all__',
                    ])
                @endforeach
            </div>
        @elseif($showEmployeeProgress ?? is_admin_employee())
            <div id="section-progress">
                @include('adminmodule::partials._employee-progress', [
                    'todayDone' => $todayDone,
                    'monthly' => $monthly,
                    'qualityStatsDaily' => $employeeData['quality_stats_daily'] ?? [],
                    'qualityStatsMonthly' => $employeeData['quality_stats_monthly'] ?? ($monthly['quality_stats'] ?? []),
                    'leaderboard' => $employeeData['leaderboard'] ?? [],
                    'teamRankRowsDaily' => $employeeData['team_rank_rows_daily'] ?? ($employeeData['team_rank_rows'] ?? []),
                    'teamRankRowsMonthly' => $employeeData['team_rank_rows_monthly'] ?? ($employeeData['team_rank_rows'] ?? []),
                    'rankMarksChart' => $employeeData['rank_marks_chart'] ?? [],
                    'progressScopeId' => 'self',
                    'highlightEmployeeId' => $employeeData['highlight_employee_id'] ?? (string) ($employeeData['user']->id ?? ''),
                    'progressLayout' => 'employee',
                ])
            </div>
        @endif

        @if(! is_admin_employee())
            <div class="mb-3 mt-2" id="section-employee-status">
                @include('adminmodule::admin.partials._staff-presence-widget')
            </div>
        @endif

    </div>
</div>
@include('adminmodule::partials._employee-progress-info-assets')
@endsection

@push('script')
<script src="{{ asset('assets/admin-module/plugins/apex/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/admin-module/js/employee-dashboard-charts.js') }}?v=20260829dash1"></script>
<script>
    'use strict';

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.work-queue-tab, .progress-tab, .tab-btn, .tab-btn-light');
        if (! btn) {
            return;
        }

        var group = btn.closest('[data-tabs]');
        if (! group) {
            return;
        }

        var container = group.closest('.work-queue-box, .card, .progress-card, .progress-shell');
        if (! container) {
            return;
        }

        var tab = btn.getAttribute('data-tab');
        group.querySelectorAll('.work-queue-tab, .progress-tab, .tab-btn, .tab-btn-light').forEach(function (b) {
            b.classList.toggle('active', b === btn);
        });
        container.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-panel') === tab);
        });
        if (tab === 'ranking-monthly' && window.PanunDashboardCharts && window.PanunDashboardCharts.refreshVisible) {
            window.PanunDashboardCharts.refreshVisible(container);
        }
    });

    function activateWorkQueueTab(box, tabKey) {
        var tabsGroup = box.querySelector('[data-tabs]');
        if (! tabsGroup) {
            return;
        }

        var targetTab = box.querySelector('[data-tab$="-' + tabKey + '"]');
        if (! targetTab) {
            return;
        }

        tabsGroup.querySelectorAll('.work-queue-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn === targetTab);
        });

        box.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.classList.toggle('active', panel.getAttribute('data-panel') === targetTab.getAttribute('data-tab'));
        });
    }

    function formatEmployeeFooterLabel(template, employeeName) {
        return template.replace(/:name/g, employeeName);
    }

    var activeDashboardScope = null;
    var progressScopeRequests = {};

    function progressScopePanel(scopeValue) {
        var targetId = (scopeValue === '__all__' || scopeValue === '') ? '__all__' : scopeValue;
        return document.querySelector('.js-progress-scope-panel[data-scope-id="' + targetId + '"]');
    }

    function setProgressScopeLoading(isLoading) {
        var loading = document.querySelector('.js-progress-scope-loading');
        if (loading) {
            loading.classList.toggle('d-none', ! isLoading);
        }
    }

    function ensureProgressScope(scopeValue) {
        var existing = progressScopePanel(scopeValue);
        if (existing) {
            return Promise.resolve(existing);
        }

        var wrapper = document.querySelector('.js-progress-scope-wrapper');
        var url = wrapper ? wrapper.getAttribute('data-scope-url') : '';
        if (! wrapper || ! url) {
            return Promise.resolve(null);
        }

        var targetId = (scopeValue === '__all__' || scopeValue === '') ? '__all__' : scopeValue;
        if (progressScopeRequests[targetId]) {
            return progressScopeRequests[targetId];
        }

        setProgressScopeLoading(true);
        progressScopeRequests[targetId] = fetch(url + '?employee_id=' + encodeURIComponent(targetId), {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Failed to load progress');
                }
                return response.text();
            })
            .then(function (html) {
                var already = progressScopePanel(targetId);
                if (already) {
                    return already;
                }
                wrapper.insertAdjacentHTML('beforeend', html);
                return progressScopePanel(targetId);
            })
            .catch(function (error) {
                console.warn('Progress scope load failed:', error);
                return null;
            })
            .finally(function () {
                delete progressScopeRequests[targetId];
                setProgressScopeLoading(Object.keys(progressScopeRequests).length > 0);
            });

        return progressScopeRequests[targetId];
    }

    function showProgressScope(scopeValue) {
        var isAll = scopeValue === '__all__' || scopeValue === '';
        document.querySelectorAll('.js-progress-scope-panel').forEach(function (panel) {
            var panelScope = panel.getAttribute('data-scope-id') || '';
            var showPanel = isAll ? panelScope === '__all__' : panelScope === scopeValue;
            panel.classList.toggle('d-none', ! showPanel);
        });
    }

    function reloadProgressChart(scopeValue, previousScope) {
        var scopeChanged = previousScope !== null && previousScope !== scopeValue;
        var needsInitialChart = previousScope === null;

        if (window.PanunDashboardCharts && window.PanunDashboardCharts.reloadVisibleScopeChart) {
            if (scopeChanged || needsInitialChart) {
                window.PanunDashboardCharts.reloadVisibleScopeChart(scopeValue, true);
            }
        } else if ((scopeChanged || needsInitialChart) && window.PanunDashboardCharts && window.PanunDashboardCharts.refreshVisible) {
            window.PanunDashboardCharts.refreshVisible(document);
        }
    }

    function setDashboardScope(scopeValue) {
        var previousScope = activeDashboardScope;
        activeDashboardScope = scopeValue;
        var select = document.getElementById('dashboard-employee-select');
        var isAll = scopeValue === '__all__' || scopeValue === '';
        var employeeName = '';

        if (select) {
            if (select.value !== scopeValue) {
                select.value = scopeValue;
            }

            if (! isAll && select.selectedIndex >= 0) {
                employeeName = select.options[select.selectedIndex].text;
            }
        }

        document.querySelectorAll('[data-has-employee-tab]').forEach(function (box) {
            var footerEmployeeLink = box.querySelector('.js-work-queue-employee-footer-link');
            var employeeTabBtn = box.querySelector('.js-work-queue-employee-tab');
            var tabCountEl = box.querySelector('.js-work-queue-employee-tab-count');
            var labelEl = box.querySelector('.js-work-queue-employee-tab-label');
            var defaultLabel = labelEl ? (labelEl.getAttribute('data-default-label') || 'Employee') : 'Employee';

            if (isAll) {
                activateWorkQueueTab(box, 'all');

                if (employeeTabBtn) {
                    employeeTabBtn.classList.add('d-none');
                }

                if (labelEl) {
                    labelEl.textContent = defaultLabel;
                }

                if (footerEmployeeLink) {
                    footerEmployeeLink.classList.add('d-none');
                    footerEmployeeLink.textContent = footerEmployeeLink.getAttribute('data-default-label') || '';
                }

                box.querySelectorAll('.js-work-queue-employee-panel').forEach(function (panel) {
                    panel.classList.add('d-none');
                });

                return;
            }

            if (employeeTabBtn) {
                employeeTabBtn.classList.remove('d-none');
            }

            activateWorkQueueTab(box, 'employee');

            if (labelEl) {
                labelEl.textContent = employeeName || defaultLabel;
            }

            if (footerEmployeeLink) {
                footerEmployeeLink.classList.remove('d-none');
                var footerTemplate = footerEmployeeLink.getAttribute('data-employee-label-template');
                var footerDefaultLabel = footerEmployeeLink.getAttribute('data-default-label') || '';
                footerEmployeeLink.textContent = footerTemplate
                    ? formatEmployeeFooterLabel(footerTemplate, employeeName || defaultLabel)
                    : footerDefaultLabel;
            }

            var activePanel = null;

            box.querySelectorAll('.js-work-queue-employee-panel').forEach(function (panel) {
                var isActive = panel.getAttribute('data-employee-id') === scopeValue;
                panel.classList.toggle('d-none', ! isActive);
                if (isActive) {
                    activePanel = panel;
                }
            });

            if (activePanel) {
                var total = Number(activePanel.getAttribute('data-total') || 0);
                var viewAllUrl = activePanel.getAttribute('data-view-all-url') || '#';

                if (tabCountEl) {
                    tabCountEl.textContent = '(' + total + ')';
                }

                if (footerEmployeeLink) {
                    footerEmployeeLink.setAttribute('href', viewAllUrl);
                }
            }
        });

        ensureProgressScope(scopeValue).then(function () {
            if (activeDashboardScope !== scopeValue) {
                return;
            }

            showProgressScope(scopeValue);
            reloadProgressChart(scopeValue, previousScope);
        });

        try {
            localStorage.setItem('admin_dashboard_scope', scopeValue);
        } catch (error) {}
    }

    document.querySelectorAll('.js-dashboard-employee-select').forEach(function (select) {
        select.addEventListener('change', function () {
            setDashboardScope(select.value);
        });
        select.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    (function restoreDashboardScope() {
        var select = document.getElementById('dashboard-employee-select');
        if (! select) {
            if (window.PanunDashboardCharts && window.PanunDashboardCharts.init) {
                window.PanunDashboardCharts.init();
            }
            if (window.PanunDashboardCharts && window.PanunDashboardCharts.reloadVisibleScopeChart) {
                var employeeId = document.querySelector('.js-rank-marks-chart');
                var scopeValue = employeeId ? (employeeId.getAttribute('data-employee-scope') || '') : '';
                window.PanunDashboardCharts.reloadVisibleScopeChart(scopeValue, true);
            } else if (window.PanunDashboardCharts && window.PanunDashboardCharts.refreshVisible) {
                window.PanunDashboardCharts.refreshVisible(document);
            }
            return;
        }

        var storedScope = null;
        try {
            storedScope = localStorage.getItem('admin_dashboard_scope')
                || localStorage.getItem('admin_dashboard_employee_id');
        } catch (error) {}

        var scopeValue = storedScope || select.getAttribute('data-default-scope') || '__all__';

        if (window.PanunDashboardCharts && window.PanunDashboardCharts.init) {
            window.PanunDashboardCharts.init();
        }

        setDashboardScope(scopeValue);
    })();

    (function initStaffPresenceWidget() {
        if (!document.getElementById('staff-presence-widget')) {
            return;
        }

        var statusBadgeClass = {
            online: 'staff-presence-badge--online',
            away: 'staff-presence-badge--busy',
            on_break: 'staff-presence-badge--break',
            offline: 'staff-presence-badge--offline'
        };
        var statusDotClass = {
            online: 'bg-success',
            away: 'bg-warning',
            on_break: 'bg-info',
            offline: 'bg-secondary'
        };

        function refreshStaffPresenceWidget() {
            $.getJSON('{{ route('admin.staff-presence.list') }}', function (response) {
                if (!response.data || !response.data.staff) {
                    return;
                }
                var summary = response.data.summary || {};
                ['online', 'away', 'on_break', 'offline'].forEach(function (key) {
                    var el = document.querySelector('[data-summary="' + key + '"]');
                    if (el) {
                        el.textContent = summary[key] || 0;
                    }
                });
                response.data.staff.forEach(function (member) {
                    var row = document.querySelector('#staff-presence-tbody tr[data-staff-id="' + member.id + '"]');
                    if (!row) {
                        return;
                    }
                    var badge = row.querySelector('.staff-presence-badge');
                    if (badge) {
                        badge.textContent = member.presence_label;
                        badge.className = 'staff-presence-badge ' + (statusBadgeClass[member.presence_status] || 'staff-presence-badge--offline');
                    }
                    var dot = row.querySelector('.staff-presence-dot');
                    if (dot) {
                        dot.className = 'staff-presence-dot ' + (statusDotClass[member.presence_status] || 'bg-secondary');
                    }
                    var pageCell = row.querySelector('.staff-last-visited-page');
                    if (pageCell) {
                        pageCell.textContent = member.last_visited_page_label || '—';
                        pageCell.setAttribute('title', member.last_visited_page || '');
                    }
                    var map = {
                        '.staff-last-offline-today': member.last_offline_period_today,
                        '.staff-total-offline-today': member.total_offline_today,
                        '.staff-last-away-today': member.last_away_period_today,
                        '.staff-total-away-today': member.total_away_today,
                        '.staff-last-break-today': member.last_break_period_today,
                        '.staff-total-break-today': member.total_break_today,
                        '.staff-total-online-today': member.total_online_today
                    };
                    Object.keys(map).forEach(function (selector) {
                        var cell = row.querySelector(selector);
                        if (cell) {
                            cell.textContent = map[selector] || '—';
                        }
                    });
                });
            });
        }

        refreshStaffPresenceWidget();
        setInterval(refreshStaffPresenceWidget, 30000);

        var staffPresenceHistoryReady = false;

        function setStaffPresenceHistoryState(state) {
            var empty = document.getElementById('staff-presence-history-empty');
            var loading = document.getElementById('staff-presence-history-loading');
            var table = document.getElementById('staff-presence-history-table-wrap');
            if (empty) empty.classList.toggle('d-none', state !== 'empty');
            if (loading) loading.classList.toggle('d-none', state !== 'loading');
            if (table) table.classList.toggle('d-none', state !== 'table');
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderStaffPresenceHistoryRows(staff) {
            var tbody = document.getElementById('staff-presence-history-tbody');
            if (!tbody) {
                return;
            }
            tbody.innerHTML = '';
            (staff || []).forEach(function (member) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="staff-presence-employee-col"><div class="staff-presence-person">' +
                        '<div class="staff-presence-avatar-wrap">' +
                        '<img src="' + escapeHtml(member.profile_image || '') + '" alt="" class="staff-presence-avatar" width="28" height="28">' +
                        '</div>' +
                        '<div class="staff-presence-person__meta">' +
                        '<div class="staff-presence-person__name">' + escapeHtml(member.name || '') + '</div>' +
                        '<div class="staff-presence-person__email">' + escapeHtml(member.email || '') + '</div>' +
                        '</div></div></td>' +
                    '<td>' + escapeHtml(member.last_offline_period || '—') + '</td>' +
                    '<td>' + escapeHtml(member.total_offline || '—') + '</td>' +
                    '<td>' + escapeHtml(member.last_away_period || '—') + '</td>' +
                    '<td>' + escapeHtml(member.total_away || '—') + '</td>' +
                    '<td>' + escapeHtml(member.last_break_period || '—') + '</td>' +
                    '<td>' + escapeHtml(member.total_break || '—') + '</td>' +
                    '<td>' + escapeHtml(member.total_online || '—') + '</td>';
                tbody.appendChild(tr);
            });
        }

        function loadStaffPresenceHistory(date) {
            if (!date) {
                return;
            }
            setStaffPresenceHistoryState('loading');
            $.getJSON('{{ route('admin.staff-presence.history') }}', { date: date }, function (response) {
                if (!response.data || !response.data.staff) {
                    setStaffPresenceHistoryState('empty');
                    return;
                }
                renderStaffPresenceHistoryRows(response.data.staff);
                setStaffPresenceHistoryState('table');
            }).fail(function () {
                setStaffPresenceHistoryState('empty');
            });
        }

        function initStaffPresenceHistoryCalendar() {
            var input = document.getElementById('staff-presence-history-date');
            if (!input) {
                return;
            }

            setStaffPresenceHistoryState('loading');

            $.getJSON('{{ route('admin.staff-presence.history-dates') }}', function (response) {
                var dates = (response.data && response.data.dates) ? response.data.dates : [];
                var today = input.getAttribute('max') || new Date().toISOString().slice(0, 10);

                if (!dates.length) {
                    input.value = today;
                    input.min = today;
                    input.max = today;
                    staffPresenceHistoryReady = true;
                    setStaffPresenceHistoryState('empty');
                    return;
                }

                var latest = dates[0].value;
                var oldest = dates[dates.length - 1].value;
                input.min = oldest;
                input.max = today;
                input.value = latest;
                staffPresenceHistoryReady = true;
                loadStaffPresenceHistory(latest);
            }).fail(function () {
                staffPresenceHistoryReady = true;
                setStaffPresenceHistoryState('empty');
            });
        }

        $('#staffPresenceHistoryModal').on('show.bs.modal', function () {
            if (!staffPresenceHistoryReady) {
                initStaffPresenceHistoryCalendar();
            }
        });

        $('#staff-presence-history-date').on('change', function () {
            loadStaffPresenceHistory(this.value);
        });

        $('#staff-presence-history-date').on('click focus', function () {
            if (typeof this.showPicker === 'function') {
                try {
                    this.showPicker();
                } catch (error) {}
            }
        });
    })();
</script>
@endpush
