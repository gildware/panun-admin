@extends('adminmodule::layouts.new-master')

@section('title', translate('API_logs'))

@push('css_or_js')
    <style>
        .calls-filter-offcanvas { display: flex; flex-direction: column; }
        .calls-filter-offcanvas .calls-filter-form { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .calls-filter-offcanvas .calls-filter-body { flex: 1; min-height: 0; }
        .calls-filter-offcanvas .calls-filter-footer { flex-shrink: 0; }
        .calls-list-card .card-body { padding: 0.5rem 0.75rem 0.75rem; }
        .calls-list-table { font-size: 0.8125rem; }
        .calls-list-table th,
        .calls-list-table td { padding: 0.3rem 0.5rem; white-space: nowrap; vertical-align: middle; }
        .calls-list-table thead th { font-size: 0.75rem; font-weight: 600; }
        .calls-list-table .material-icons { font-size: 14px; line-height: 1; }
        .calls-list-card .pagination { margin-bottom: 0; }
        .calls-dir-toggle {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            gap: 0.1rem;
        }
        .calls-dir-toggle a {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.28rem 0.7rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #4b5563;
            text-decoration: none;
            line-height: 1.2;
        }
        .calls-dir-toggle a.is-active { background: #111827; color: #fff; }
        .call-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.12rem 0.5rem 0.12rem 0.35rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .call-pill .material-icons { font-size: 14px; }
        .call-pill--webhook { background: #e7f0ff; color: #1d4ed8; }
        .call-pill--api { background: #eef2ff; color: #4338ca; }
        .call-pill--ok { background: #e7f6ee; color: #0f7a3a; }
        .call-pill--bad { background: #fdecea; color: #c62828; }
        .call-pill--warn { background: #fff4e5; color: #b45309; }
        .call-pill--muted { background: #f1f3f5; color: #6b7280; }
        .calls-log-message { max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
    </style>
@endpush

@section('content')
    @php
        $filtersAppliedCount = collect(['q', 'result', 'from', 'to'])
            ->filter(fn ($key) => request()->filled($key))
            ->count();
        $kind = $kind ?? '';
        $toggleQuery = request()->except(['kind', 'page']);
        $kindReset = $kind !== '' ? ['kind' => $kind] : [];
        $resultMeta = [
            'received' => ['call-pill--ok', 'webhook'],
            'success' => ['call-pill--ok', 'check'],
            'ignored' => ['call-pill--muted', 'block'],
            'empty' => ['call-pill--warn', 'inbox'],
            'unauthorized' => ['call-pill--bad', 'lock'],
            'failed' => ['call-pill--bad', 'error'],
        ];
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-2 mb-2">
                <div>
                    <h2 class="page-title mb-0">{{ translate('API_logs') }}</h2>
                    <p class="text-muted small mb-0">
                        {{ translate('Superfone_posts_every_finished_call_to') }}
                        <code>{{ url('/webhooks/superfone/calls') }}</code>
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="calls-dir-toggle" role="group" aria-label="{{ translate('API_logs') }}">
                        <a href="{{ route('admin.calls.api-logs', $toggleQuery) }}" class="{{ $kind === '' ? 'is-active' : '' }}">
                            {{ translate('All') }}
                        </a>
                        <a href="{{ route('admin.calls.api-logs', array_merge($toggleQuery, ['kind' => 'webhook'])) }}"
                           class="{{ $kind === 'webhook' ? 'is-active' : '' }}">
                            <span class="material-icons">sync_alt</span>
                            {{ translate('Webhooks') }}
                        </a>
                        <a href="{{ route('admin.calls.api-logs', array_merge($toggleQuery, ['kind' => 'api'])) }}"
                           class="{{ $kind === 'api' ? 'is-active' : '' }}">
                            <span class="material-icons">cloud_download</span>
                            {{ translate('API') }}
                        </a>
                    </div>
                    <button type="button"
                            class="btn btn-outline-primary d-inline-flex align-items-center gap-2 position-relative"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#callsLogFilterDrawer"
                            aria-controls="callsLogFilterDrawer">
                        <span class="material-icons">filter_list</span>
                        {{ translate('Filter') }}
                        @if($filtersAppliedCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $filtersAppliedCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="offcanvas offcanvas-end calls-filter-offcanvas" tabindex="-1" id="callsLogFilterDrawer" aria-labelledby="callsLogFilterDrawerLabel" style="width: 360px; max-width: 95vw;">
                <div class="offcanvas-header border-bottom">
                    <h5 class="offcanvas-title" id="callsLogFilterDrawerLabel">{{ translate('Filter') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ translate('Close') }}"></button>
                </div>
                <form method="get" action="{{ route('admin.calls.api-logs') }}" class="calls-filter-form">
                    @if($kindReset)
                        <input type="hidden" name="kind" value="{{ $kindReset['kind'] }}">
                    @endif
                    <div class="offcanvas-body pt-3 overflow-auto flex-grow-1 calls-filter-body">
                        <div class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label" for="log-q">{{ translate('Search') }}</label>
                                <input id="log-q" type="text" name="q" value="{{ request('q') }}" class="form-control"
                                       placeholder="{{ translate('Event_call_id_or_message') }}">
                            </div>
                            <div>
                                <label class="form-label" for="log-result">{{ translate('Result') }}</label>
                                <select id="log-result" name="result" class="form-select">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach(['received', 'success', 'ignored', 'empty', 'unauthorized', 'failed'] as $resultOption)
                                        <option value="{{ $resultOption }}" @selected(request('result') === $resultOption)>{{ translate(ucfirst($resultOption)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="log-from">{{ translate('From') }}</label>
                                <input id="log-from" type="date" name="from" value="{{ request('from') }}" class="form-control">
                            </div>
                            <div>
                                <label class="form-label" for="log-to">{{ translate('To') }}</label>
                                <input id="log-to" type="date" name="to" value="{{ request('to') }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="calls-filter-footer border-top bg-body p-3">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.calls.api-logs', $kindReset) }}" class="btn btn--secondary flex-grow-1">{{ translate('Reset') }}</a>
                            <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card calls-list-card">
                <div class="card-body table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0 calls-list-table">
                        <thead>
                        <tr>
                            <th>{{ translate('Time') }}</th>
                            <th>{{ translate('Type') }}</th>
                            <th>{{ translate('Event') }}</th>
                            <th>{{ translate('Call_id') }}</th>
                            <th>{{ translate('Result') }}</th>
                            <th>{{ translate('HTTP') }}</th>
                            <th>{{ translate('Duration') }}</th>
                            <th>{{ translate('Message') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            @php
                                [$resultClass, $resultIcon] = $resultMeta[$log->result] ?? ['call-pill--muted', 'help_outline'];
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.calls.api-logs.show', $log) }}">
                                        {{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    @if($log->kind === 'webhook')
                                        <span class="call-pill call-pill--webhook">
                                            <span class="material-icons">sync_alt</span>
                                            {{ translate('Webhook') }}
                                        </span>
                                    @else
                                        <span class="call-pill call-pill--api">
                                            <span class="material-icons">cloud_download</span>
                                            {{ translate('API') }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $log->event ?: '—' }}</td>
                                <td><code>{{ $log->cdr_uuid ?: '—' }}</code></td>
                                <td>
                                    <span class="call-pill {{ $resultClass }}">
                                        <span class="material-icons">{{ $resultIcon }}</span>
                                        {{ translate(ucfirst((string) $log->result)) }}
                                    </span>
                                </td>
                                <td>{{ $log->http_status ?: '—' }}</td>
                                <td>{{ $log->duration_ms !== null ? $log->duration_ms.' ms' : '—' }}</td>
                                <td class="calls-log-message" title="{{ $log->message }}">{{ $log->message ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">{{ translate('No_API_logs_yet') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div class="mt-2">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
