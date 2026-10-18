@extends('adminmodule::layouts.new-master')

@section('title', translate('API_log'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-2 mb-3">
                <h2 class="page-title mb-0">{{ translate('API_log') }}</h2>
                <a href="{{ route('admin.calls.api-logs') }}" class="btn btn-outline-secondary">{{ translate('Back') }}</a>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">{{ translate('Time') }}</dt>
                                <dd class="col-sm-8">{{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('Type') }}</dt>
                                <dd class="col-sm-8">{{ $log->kind === 'webhook' ? translate('Webhook') : translate('API') }}</dd>

                                <dt class="col-sm-4">{{ translate('Event') }}</dt>
                                <dd class="col-sm-8">{{ $log->event ?: '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('Call_id') }}</dt>
                                <dd class="col-sm-8">
                                    @if($call)
                                        <a href="{{ route('admin.calls.show', $call) }}"><code>{{ $log->cdr_uuid }}</code></a>
                                    @else
                                        <code>{{ $log->cdr_uuid ?: '—' }}</code>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">{{ translate('Result') }}</dt>
                                <dd class="col-sm-8">{{ translate(ucfirst((string) $log->result)) }}</dd>

                                <dt class="col-sm-4">{{ translate('HTTP') }}</dt>
                                <dd class="col-sm-8">{{ $log->method ?: '—' }} {{ $log->http_status ?: '' }}</dd>

                                <dt class="col-sm-4">{{ translate('Duration') }}</dt>
                                <dd class="col-sm-8">{{ $log->duration_ms !== null ? $log->duration_ms.' ms' : '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('URL') }}</dt>
                                <dd class="col-sm-8 text-break">{{ $log->url ?: '—' }}</dd>

                                <dt class="col-sm-4">{{ translate('Message') }}</dt>
                                <dd class="col-sm-8">{{ $log->message ?: '—' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="h5">{{ translate('Payload') }}</h3>
                            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ $log->payload ? json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
