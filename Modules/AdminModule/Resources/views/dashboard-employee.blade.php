@extends('adminmodule::layouts.new-master')

@section('title', translate('dashboard'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-dashboard.css') }}?v=20260818rank3">
<link rel="stylesheet" href="{{ asset('assets/admin-module/css/employee-progress-premium.css') }}?v=20260818rank3">
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
</script>
@endpush
