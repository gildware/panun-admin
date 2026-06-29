@extends('adminmodule::layouts.master')

@section('title', translate('In_App_Call_Monitor'))

@push('css_or_js')
    <style>
        .in-app-call-live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            animation: in-app-call-pulse 1.2s ease-in-out infinite;
        }
        @keyframes in-app-call-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .35; transform: scale(.85); }
        }
        .in-app-call-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #198754;
        }
        .in-app-call-summary-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 14px 16px;
            background: #fff;
            height: 100%;
        }
        .in-app-call-summary-card__label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .in-app-call-summary-card__value {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.1;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <h2 class="page-title mb-1">{{ translate('In_App_Call_Monitor') }}</h2>
                    <p class="text-muted mb-0">{{ translate('In_App_Call_Monitor_help') }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="in-app-call-live-badge">
                        <span class="in-app-call-live-dot"></span>
                        {{ translate('Live') }}
                    </span>
                    <span class="text-muted small" id="in-app-call-last-updated">—</span>
                </div>
            </div>

            @if(! $isEnabled)
                <div class="alert alert-warning">
                    {{ translate('In_app_calling_is_not_configured') }}
                </div>
            @endif

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="in-app-call-summary-card">
                        <div class="in-app-call-summary-card__label">{{ translate('Active_Calls') }}</div>
                        <div class="in-app-call-summary-card__value text-success" id="in-app-call-active-count">{{ count($activeCalls) }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="in-app-call-summary-card">
                        <div class="in-app-call-summary-card__label">{{ translate('Ringing') }}</div>
                        <div class="in-app-call-summary-card__value text-warning" id="in-app-call-ringing-count">
                            {{ collect($activeCalls)->where('status', 'ringing')->count() }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="in-app-call-summary-card">
                        <div class="in-app-call-summary-card__label">{{ translate('Connected') }}</div>
                        <div class="in-app-call-summary-card__value text-primary" id="in-app-call-connected-count">
                            {{ collect($activeCalls)->where('status', 'accepted')->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <h5 class="mb-0">{{ translate('Live_Active_Calls') }}</h5>
                    <span class="text-muted small">{{ translate('Auto_refreshes_every_5_seconds') }}</span>
                </div>
                <div class="card-body" id="in-app-call-active-panel">
                    @include('inappcallmodule::admin.partials._active_calls', ['activeCalls' => $activeCalls])
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ translate('Call_History') }}</h5>
                </div>
                <div class="card-body">
                    <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('admin.in-app-calls.index') }}">
                        <div class="col-md-3">
                            <label class="form-label mb-1">{{ translate('status') }}</label>
                            <select class="form-control" name="status">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">{{ translate('From') }}</label>
                            <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">{{ translate('To') }}</label>
                            <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">{{ translate('Search') }}</label>
                            <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ translate('Name_or_phone') }}">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn--primary">
                                <span class="material-icons">filter_alt</span> {{ translate('Filter') }}
                            </button>
                            <a href="{{ route('admin.in-app-calls.index') }}" class="btn btn--secondary">
                                {{ translate('Reset') }}
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="align-middle text-nowrap">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Caller') }}</th>
                                <th>{{ translate('Callee') }}</th>
                                <th>{{ translate('status') }}</th>
                                <th>{{ translate('Connection') }}</th>
                                <th>{{ translate('Duration') }}</th>
                                <th>{{ translate('Started_At') }}</th>
                                <th>{{ translate('Ended_At') }}</th>
                                <th>{{ translate('Booking_ID') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($logs as $key => $call)
                                <tr>
                                    <td>{{ $key + $logs->firstItem() }}</td>
                                    <td>@include('inappcallmodule::admin.partials._user_cell', ['user' => $call['caller']])</td>
                                    <td>@include('inappcallmodule::admin.partials._user_cell', ['user' => $call['callee']])</td>
                                    <td>
                                        <span class="badge badge-{{ $call['status_badge'] }}">{{ $call['status_label'] }}</span>
                                    </td>
                                    <td class="text-muted">{{ $call['connection_label'] }}</td>
                                    <td>{{ $call['duration_label'] }}</td>
                                    <td class="text-nowrap text-muted">{{ $call['started_at'] ?? '—' }}</td>
                                    <td class="text-nowrap text-muted">{{ $call['ended_at'] ?? '—' }}</td>
                                    <td>
                                        @if($call['booking_url'])
                                            <a href="{{ $call['booking_url'] }}" class="c1">#{{ $call['reference_id'] }}</a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        {{ translate('No_results_found') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        {!! $logs->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var activeUrl = @json(route('admin.in-app-calls.active'));
            var pollMs = 5000;
            var lastActiveCount = {{ count($activeCalls) }};

            function formatDuration(seconds) {
                seconds = Math.max(0, parseInt(seconds || 0, 10));
                if (seconds < 60) return seconds + 's';
                var minutes = Math.floor(seconds / 60);
                var remaining = seconds % 60;
                return minutes + 'm ' + remaining + 's';
            }

            function tickElapsedTimers() {
                document.querySelectorAll('[data-elapsed-seconds]').forEach(function (cell) {
                    var seconds = parseInt(cell.getAttribute('data-elapsed-seconds') || '0', 10) + 1;
                    cell.setAttribute('data-elapsed-seconds', String(seconds));
                    cell.textContent = formatDuration(seconds);
                });
            }

            function updateSummaryCounts() {
                var rows = document.querySelectorAll('#in-app-call-active-panel tbody tr[data-call-status]');
                var ringing = 0;
                var connected = 0;
                rows.forEach(function (row) {
                    var status = row.getAttribute('data-call-status');
                    if (status === 'ringing') ringing++;
                    if (status === 'accepted') connected++;
                });
                var activeCount = rows.length;
                var activeEl = document.getElementById('in-app-call-active-count');
                var ringingEl = document.getElementById('in-app-call-ringing-count');
                var connectedEl = document.getElementById('in-app-call-connected-count');
                if (activeEl) activeEl.textContent = activeCount;
                if (ringingEl) ringingEl.textContent = ringing;
                if (connectedEl) connectedEl.textContent = connected;
            }

            function setLastUpdated() {
                var el = document.getElementById('in-app-call-last-updated');
                if (!el) return;
                var now = new Date();
                el.textContent = @json(translate('Updated')) + ': ' + now.toLocaleTimeString();
            }

            function refreshActiveCalls() {
                $.getJSON(activeUrl, function (response) {
                    var panel = document.getElementById('in-app-call-active-panel');
                    if (panel && response.html !== undefined) {
                        panel.innerHTML = response.html;
                    }
                    if (typeof response.count === 'number' && response.count !== lastActiveCount) {
                        lastActiveCount = response.count;
                    }
                    updateSummaryCounts();
                    setLastUpdated();
                });
            }

            setInterval(tickElapsedTimers, 1000);
            refreshActiveCalls();
            setInterval(refreshActiveCalls, pollMs);
        })();
    </script>
@endpush
