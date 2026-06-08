@php
    $listRoute = $listRoute ?? route('admin.voice-call.api-logs');
@endphp

<div class="alert alert-info mb-3">
    {{ translate('OmniDimension_api_logs_hint') }}
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $listRoute }}" id="voice-api-logs-filter-form" class="voice-call-filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('search_here'),
                        'hint' => translate('Voice_field_hint_api_logs_search'),
                    ])
                    <input type="text"
                           class="form-control"
                           name="search"
                           value="{{ $filterSearch ?? '' }}"
                           placeholder="{{ translate('OmniDimension_api_logs_search_placeholder') }}">
                </div>
                <div class="col-md-2">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('Method'),
                        'hint' => translate('Voice_field_hint_api_logs_method'),
                    ])
                    <select class="form-select js-select" name="method">
                        <option value="">{{ translate('All') }}</option>
                        @foreach(['GET', 'POST'] as $methodOption)
                            <option value="{{ $methodOption }}"
                                    {{ strtoupper((string) ($filterMethod ?? '')) === $methodOption ? 'selected' : '' }}>
                                {{ $methodOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('Status'),
                        'hint' => translate('Voice_field_hint_api_logs_status'),
                    ])
                    <select class="form-select js-select" name="status">
                        <option value="">{{ translate('All') }}</option>
                        <option value="success" {{ ($filterStatus ?? '') === 'success' ? 'selected' : '' }}>{{ translate('Success') }}</option>
                        <option value="failed" {{ ($filterStatus ?? '') === 'failed' ? 'selected' : '' }}>{{ translate('Failed') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn--primary" type="submit">{{ translate('Search') }}</button>
                    <button class="btn btn--secondary voice-api-logs-reset" type="button">{{ translate('Reset') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-30">
        @if($logs->isEmpty())
            <div class="text-center text-muted py-4">{{ translate('no_data_found') }}</div>
        @else
            <div class="table-responsive voice-call-table-wrap">
                <table class="table table-hover align-middle voice-call-data-table voice-api-logs-table">
                    <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Method') }}</th>
                        <th>{{ translate('API') }}</th>
                        <th>{{ translate('HTTP_Status') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Duration') }}</th>
                        <th>{{ translate('Error') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $index => $log)
                        @php
                            $rowNum = ($logs->currentPage() - 1) * $logs->perPage() + $index + 1;
                            $queryJson = $log->query_params ? json_encode($log->query_params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
                            $requestJson = $log->request_body ?? '';
                            $responseJson = $log->response_body ?? '';
                        @endphp
                        <tr class="voice-api-log-row" data-log-id="{{ $log->id }}">
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary voice-api-log-toggle"
                                        aria-expanded="false"
                                        aria-label="{{ translate('View_details') }}">
                                    <span class="material-icons" style="font-size:16px;">expand_more</span>
                                </button>
                            </td>
                            <td>{{ $rowNum }}</td>
                            <td class="text-nowrap small">{{ $log->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $log->method }}</span></td>
                            <td><code class="small">{{ $log->path }}</code></td>
                            <td>{{ $log->http_status ?? '—' }}</td>
                            <td>
                                @if($log->ok)
                                    <span class="badge bg-success">{{ translate('Success') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ translate('Failed') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap small">
                                @if($log->duration_ms !== null)
                                    {{ $log->duration_ms }} ms
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small text-danger text-truncate" style="max-width:180px;" title="{{ $log->error }}">
                                {{ $log->error ?: '—' }}
                            </td>
                        </tr>
                        <tr class="voice-api-log-details d-none" data-log-id="{{ $log->id }}">
                            <td colspan="9" class="bg-light">
                                <div class="row g-3 p-2">
                                    @if($queryJson !== '')
                                        <div class="col-md-4">
                                            <div class="fw-semibold small mb-1">{{ translate('Query_params') }}</div>
                                            <pre class="small mb-0 p-2 bg-white border rounded" style="max-height:220px;overflow:auto;">{{ $queryJson }}</pre>
                                        </div>
                                    @endif
                                    <div class="{{ $queryJson !== '' ? 'col-md-4' : 'col-md-6' }}">
                                        <div class="fw-semibold small mb-1">{{ translate('Request') }}</div>
                                        <pre class="small mb-0 p-2 bg-white border rounded" style="max-height:220px;overflow:auto;">{{ $requestJson !== '' ? $requestJson : '—' }}</pre>
                                    </div>
                                    <div class="{{ $queryJson !== '' ? 'col-md-4' : 'col-md-6' }}">
                                        <div class="fw-semibold small mb-1">{{ translate('Response') }}</div>
                                        <pre class="small mb-0 p-2 bg-white border rounded" style="max-height:220px;overflow:auto;">{{ $responseJson !== '' ? $responseJson : '—' }}</pre>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="d-flex justify-content-end mt-3">
                    <nav>
                        <ul class="pagination mb-0">
                            @if($logs->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                            @else
                                <li class="page-item">
                                    <a class="page-link voice-api-logs-page-link" href="#" data-page="{{ $logs->currentPage() - 1 }}">&laquo;</a>
                                </li>
                            @endif

                            @for($p = max(1, $logs->currentPage() - 2); $p <= min($logs->lastPage(), $logs->currentPage() + 2); $p++)
                                <li class="page-item {{ $p === $logs->currentPage() ? 'active' : '' }}">
                                    <a class="page-link voice-api-logs-page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                                </li>
                            @endfor

                            @if($logs->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link voice-api-logs-page-link" href="#" data-page="{{ $logs->currentPage() + 1 }}">&raquo;</a>
                                </li>
                            @else
                                <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        @endif
    </div>
</div>
