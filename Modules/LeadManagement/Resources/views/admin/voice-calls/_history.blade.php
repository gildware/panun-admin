@if(!$configured)
    <div class="alert alert-warning mb-0">
        {{ translate('OmniDimension_not_configured_hint') }}
        <code>OMNIDIMENSION_API_KEY</code>
    </div>
@elseif($historyError)
    <div class="alert alert-danger mb-0">
        {{ translate('OmniDimension_call_history_failed') }}
        <span class="d-block small mt-1 text-muted">{{ $historyError }}</span>
    </div>
@else
    @php
        $historyColspan = 11 + (auth()->user()?->can('lead_outbound_enquiry_delete') ? 1 : 0);
        $listRoute = $listRoute ?? route('admin.voice-call.history');
        $filterFormId = $filterFormId ?? 'voice-history-filter-form';
        $pageLinkClass = $pageLinkClass ?? 'voice-history-page-link';
        $resetButtonClass = $resetButtonClass ?? 'voice-history-reset';
        $listMode = (string) ($listMode ?? 'history');
    @endphp
    @if($listMode === 'forwarded')
        <div class="alert alert-info mb-3">
            {{ translate('Forwarded_calls_tab_hint') }}
        </div>
    @elseif($listMode === 'callback')
        <div class="alert alert-info mb-3">
            {{ translate('Callback_calls_tab_hint') }}
        </div>
    @endif
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ $listRoute }}" id="{{ $filterFormId }}" class="voice-call-filter-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('search_here'),
                            'hint' => translate('Voice_field_hint_history_search'),
                        ])
                        <input type="text"
                               class="form-control"
                               name="search"
                               value="{{ $filterSearch ?? '' }}"
                               placeholder="{{ translate('Voice_call_search_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('OmniDimension_Agent'),
                            'hint' => translate('Voice_field_hint_history_agent'),
                        ])
                        <select class="form-select js-select" name="agent_id">
                            <option value="">{{ translate('All') }}</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent['id'] }}"
                                        {{ (string) ($filterAgentId ?? '') === (string) $agent['id'] ? 'selected' : '' }}>
                                    {{ $agent['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        @include('leadmanagement::admin.voice-calls._form_field_label', [
                            'label' => translate('Call_Status'),
                            'hint' => translate('Voice_field_hint_history_status'),
                        ])
                        <select class="form-select js-select" name="call_status">
                            <option value="">{{ translate('All') }}</option>
                            @foreach(['completed', 'busy', 'failed', 'no-answer'] as $statusOption)
                                <option value="{{ $statusOption }}"
                                        {{ ($filterCallStatus ?? '') === $statusOption ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('-', ' ', $statusOption)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn--primary" type="submit">{{ translate('Search') }}</button>
                        <button class="btn btn--secondary {{ $resetButtonClass }}" type="button">{{ translate('Reset') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-30">
            <div class="table-responsive voice-call-table-wrap">
                <table class="table table-hover align-middle voice-call-data-table voice-call-history-table">
                    <thead>
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Date_Time') }}</th>
                        <th>{{ translate('OmniDimension_Agent') }}</th>
                        <th>{{ translate('Customer_Name') }}</th>
                        <th>{{ translate('Call_Reason') }}</th>
                        <th>{{ translate('From') }}</th>
                        <th>{{ translate('To') }}</th>
                        <th>{{ translate('Direction') }}</th>
                        <th>{{ translate('Call_Status') }}</th>
                        <th>{{ translate('Duration') }}</th>
                        <th>{{ translate('Details') }}</th>
                        @can('lead_outbound_enquiry_delete')
                            <th class="text-center">{{ translate('Action') }}</th>
                        @endcan
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($callLogs as $key => $call)
                        @php
                            $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
                            if ($extracted !== []) {
                                $extracted = collect($extracted)->sortBy(function ($value, $key) {
                                    $text = trim((string) $value);
                                    $isFilled = $text !== ''
                                        && !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);

                                    return [$isFilled ? 0 : 1, strtolower((string) $key)];
                                })->all();
                            }
                            $dispatchContext = is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [];
                            $transcript = trim((string) ($call['transcript'] ?? ''));
                            $storedTransliteration = trim((string) ($call['transcript_transliterated'] ?? ''));
                            $summary = trim((string) ($call['sentiment_analysis_details'] ?? ''));
                            $hasRecording = !empty($call['recording_url']);
                            $hasDetails = $hasRecording || $transcript !== '' || $extracted !== [] || $summary !== '' || $dispatchContext !== [];
                            $contextCustomer = $dispatchContext['customer_name'] ?? '';
                            $contextReason = $dispatchContext['call_reason'] ?? '';
                            $contextReasonLabel = ($callReasonLabels ?? [])[$contextReason] ?? ($contextReason !== '' ? str_replace('_', ' ', ucfirst(strtolower($contextReason))) : '');
                        @endphp
                        <tr data-call-log-id="{{ $call['id'] }}">
                            <td>{{ (($historyPage ?? 1) - 1) * pagination_limit() + $key + 1 }}</td>
                            <td>{{ $call['time_of_call'] ?: '—' }}</td>
                            <td>{{ $call['bot_name'] ?: '—' }}</td>
                            <td>{{ $contextCustomer !== '' ? $contextCustomer : '—' }}</td>
                            <td>
                                @if($contextReasonLabel !== '')
                                    @include('leadmanagement::admin.voice-calls._call_reason_badge', [
                                        'reason' => $contextReason,
                                        'label' => $contextReasonLabel,
                                        'callReasonLabels' => $callReasonLabels ?? [],
                                    ])
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $call['from_number'] ?: '—' }}</td>
                            <td>{{ $call['to_number'] ?: '—' }}</td>
                            <td class="text-capitalize">{{ $call['call_direction'] ?: '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $call['call_status'] === 'completed' ? 'success' : ($call['call_status'] === 'failed' ? 'danger' : 'secondary') }}">
                                    {{ $call['call_status'] ?: '—' }}
                                </span>
                                @if(!empty($call['sentiment_score']))
                                    <div class="small text-muted mt-1">{{ translate('Sentiment') }}: {{ $call['sentiment_score'] }}</div>
                                @endif
                            </td>
                            <td>{{ $call['call_duration'] ?: '—' }}</td>
                            <td>
                                @if($hasDetails)
                                    <button type="button"
                                            class="btn btn-sm btn--primary voice-call-details-toggle"
                                            aria-expanded="false">
                                        {{ translate('View') }}
                                    </button>
                                @else
                                    —
                                @endif
                            </td>
                            @can('lead_outbound_enquiry_delete')
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger voice-call-history-delete"
                                            title="{{ translate('Delete') }}"
                                            data-call-id="{{ $call['id'] }}"
                                            data-call-label="{{ $call['time_of_call'] }} · {{ $call['to_number'] }}">
                                        <span class="material-icons" style="font-size:18px;">delete</span>
                                    </button>
                                </td>
                            @endcan
                        </tr>
                        @if($hasDetails)
                            <tr class="voice-call-details-row d-none">
                                <td colspan="{{ $historyColspan }}" class="p-0 border-0">
                                    @include('leadmanagement::admin.voice-calls._call_log_details_panel', [
                                        'call' => $call,
                                        'dispatchContext' => $dispatchContext,
                                        'callReasonLabels' => $callReasonLabels ?? [],
                                    ])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="{{ $historyColspan }}" class="text-center text-muted py-4">
                                {{ match ($listMode) {
                                    'forwarded' => translate('No_forwarded_calls_found'),
                                    'callback' => translate('No_callback_calls_found'),
                                    default => translate('No_data_found'),
                                } }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(($callLogsTotal ?? 0) > pagination_limit())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <span class="text-muted small">
                        {{ translate('Total') }}: {{ $callLogsTotal }}
                    </span>
                    <nav>
                        @php
                            $totalPages = (int) ceil($callLogsTotal / pagination_limit());
                            $currentPage = $historyPage ?? 1;
                        @endphp
                        <ul class="pagination mb-0">
                            @if($currentPage > 1)
                                <li class="page-item">
                                    <a class="page-link {{ $pageLinkClass }}" href="#"
                                       data-page="{{ $currentPage - 1 }}"
                                       data-agent-id="{{ $filterAgentId ?? '' }}"
                                       data-call-status="{{ $filterCallStatus ?? '' }}"
                                       data-search="{{ $filterSearch ?? '' }}">
                                        {{ translate('Previous') }}
                                    </a>
                                </li>
                            @endif
                            @if($currentPage < $totalPages)
                                <li class="page-item">
                                    <a class="page-link {{ $pageLinkClass }}" href="#"
                                       data-page="{{ $currentPage + 1 }}"
                                       data-agent-id="{{ $filterAgentId ?? '' }}"
                                       data-call-status="{{ $filterCallStatus ?? '' }}"
                                       data-search="{{ $filterSearch ?? '' }}">
                                        {{ translate('Next') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endif
