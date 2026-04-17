@extends('adminmodule::layouts.master')

@section('title', translate('System_Logs'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <h2 class="page-title">{{ translate('System_Logs') }}</h2>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('admin.system-logs.index', request()->query()) }}" class="btn btn--secondary btn-sm">
                                <span class="material-icons">refresh</span> {{ translate('Refresh') }}
                            </a>

                            <form id="clear-system-logs-form" action="{{ route('admin.system-logs.clear') }}" method="POST">
                                @csrf
                                <button type="button"
                                        class="btn btn--danger btn-sm form-alert"
                                        data-id="clear-system-logs-form"
                                        data-message="{{ translate('Are_you_sure_you_want_to_clear_the_log_file') }}">
                                    <span class="material-icons">delete_sweep</span> {{ translate('Clear_Logs') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <div><strong>{{ translate('File') }}:</strong> {{ $logFilePath }}</div>
                                    <div><strong>{{ translate('Status') }}:</strong>
                                        @if($logFileExists)
                                            <span class="text-success">{{ translate('Available') }}</span>
                                        @else
                                            <span class="text-danger">{{ translate('Not_found') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <form class="row g-2 align-items-end mt-3" method="GET" action="{{ route('admin.system-logs.index') }}">
                                <div class="col-md-3">
                                    <label class="form-label mb-1">{{ translate('Level') }}</label>
                                    <select class="form-control" name="level">
                                        @foreach(['ERROR','CRITICAL','ALERT','EMERGENCY','WARNING','INFO','DEBUG','ALL'] as $opt)
                                            <option value="{{ $opt }}" {{ $level === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label mb-1">{{ translate('Search') }}</label>
                                    <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="{{ translate('Search') }}...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label mb-1">{{ translate('Lines') }}</label>
                                    <input type="number" min="50" max="5000" class="form-control" name="lines" value="{{ (int)$lines }}">
                                </div>
                                <div class="col-md-2 d-flex gap-2">
                                    <button type="submit" class="btn btn--primary w-100">
                                        <span class="material-icons">filter_alt</span> {{ translate('Filter') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @if(! $logFileExists)
                                <div class="text-center text-muted py-5">
                                    {{ translate('Log_file_not_found_or_not_readable') }}
                                </div>
                            @else
                                @if(empty($items))
                                    <div class="text-center text-muted py-5">
                                        {{ translate('No_results_found') }}
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                            <tr>
                                                <th style="width: 160px;">{{ translate('Level') }}</th>
                                                <th>{{ translate('Message') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($items as $line)
                                                @php
                                                    $levelLabel = '—';
                                                    if (preg_match('/\.\s*([A-Z]+)\s*:/', $line, $m)) {
                                                        $levelLabel = strtoupper($m[1]);
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="text-nowrap">
                                                        @if($levelLabel === 'ERROR' || $levelLabel === 'CRITICAL' || $levelLabel === 'ALERT' || $levelLabel === 'EMERGENCY')
                                                            <span class="badge bg-danger">{{ $levelLabel }}</span>
                                                        @elseif($levelLabel === 'WARNING')
                                                            <span class="badge bg-warning text-dark">{{ $levelLabel }}</span>
                                                        @elseif($levelLabel === 'INFO')
                                                            <span class="badge bg-info text-dark">{{ $levelLabel }}</span>
                                                        @elseif($levelLabel === 'DEBUG')
                                                            <span class="badge bg-secondary">{{ $levelLabel }}</span>
                                                        @else
                                                            <span class="badge bg-light text-dark">{{ $levelLabel }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <pre class="mb-0" style="white-space: pre-wrap; word-break: break-word;">{{ $line }}</pre>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

