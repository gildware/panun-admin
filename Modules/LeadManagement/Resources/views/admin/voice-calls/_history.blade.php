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
                        <label class="form-label">{{ translate('search_here') }}</label>
                        <input type="text"
                               class="form-control"
                               name="search"
                               value="{{ $filterSearch ?? '' }}"
                               placeholder="{{ translate('Voice_call_search_placeholder') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ translate('OmniDimension_Agent') }}</label>
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
                        <label class="form-label">{{ translate('Call_Status') }}</label>
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
            <div class="table-responsive">
                <table class="table table-hover align-middle voice-call-history-table">
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
                                    <span class="badge bg-info text-dark">{{ $contextReasonLabel }}</span>
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
                            @php
                                $extractedIsFilled = function ($value): bool {
                                    $text = trim((string) $value);
                                    if ($text === '') {
                                        return false;
                                    }

                                    return !in_array(strtolower($text), ['—', '-', 'n/a', 'na', 'none', 'null'], true);
                                };
                                $extractedCopyLines = [];
                                $hasEmptyExtracted = false;
                                foreach ($extracted as $varKey => $varValue) {
                                    if ($extractedIsFilled($varValue)) {
                                        $extractedCopyLines[] = str_replace('_', ' ', ucfirst($varKey)) . ': ' . $varValue;
                                    } else {
                                        $hasEmptyExtracted = true;
                                    }
                                }
                                $extractedCopyText = implode("\n", $extractedCopyLines);
                                $transcriptLines = $transcript !== '' ? preg_split('/\r\n|\r|\n/', $transcript) : [];
                                $transcriptHasDevanagari = $transcript !== '' && preg_match('/[\x{0900}-\x{097F}]/u', $transcript) === 1;
                            @endphp
                            <tr class="voice-call-details-row d-none">
                                <td colspan="{{ $historyColspan }}" class="p-0 border-0">
                                    <div class="voice-call-details-panel p-3">
                                        @if($dispatchContext !== [])
                                            <div class="voice-call-dispatch-chips">
                                                @foreach($dispatchContext as $varKey => $varValue)
                                                    <div class="voice-call-dispatch-chip">
                                                        <span class="voice-call-dispatch-chip__label">
                                                            @if($varKey === 'call_reason')
                                                                {{ translate('Call_Reason') }}
                                                            @else
                                                                {{ str_replace('_', ' ', ucfirst($varKey)) }}
                                                            @endif
                                                        </span>
                                                        <span class="voice-call-dispatch-chip__value">
                                                            @if($varKey === 'call_reason')
                                                                {{ ($callReasonLabels ?? [])[$varValue] ?? $varValue }}
                                                            @else
                                                                {{ $varValue }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="row g-2 mb-2 voice-call-details-top-row">
                                            <div class="col-lg-3 d-flex">
                                                <div class="voice-call-left-stack d-flex flex-column gap-2 flex-grow-1">
                                                    <div class="card voice-call-detail-box shadow-sm voice-call-recording-card">
                                                        <div class="card-header voice-call-detail-box__header">
                                                            <div class="voice-call-detail-box__header-title">
                                                                <span class="material-icons" aria-hidden="true">graphic_eq</span>
                                                                <span>{{ translate('Recording') }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="card-body voice-call-recording-box">
                                                            @if($hasRecording)
                                                                <audio controls
                                                                       preload="none"
                                                                       class="w-100 voice-call-audio-player"
                                                                       data-play-url="{{ route('admin.voice-call.recording', $call['id']) }}"></audio>
                                                            @else
                                                                <p class="text-muted mb-0 small">{{ translate('No_recording_available') }}</p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="card voice-call-detail-box shadow-sm voice-call-summary-card">
                                                        <div class="card-header voice-call-detail-box__header">
                                                            <div class="voice-call-detail-box__header-title">
                                                                <span class="material-icons" aria-hidden="true">summarize</span>
                                                                <span>{{ translate('Call_Summary') }}</span>
                                                            </div>
                                                            @if($summary !== '')
                                                                <button type="button"
                                                                        class="voice-call-copy-btn"
                                                                        title="{{ translate('Copy') }}"
                                                                        data-copy-b64="{{ base64_encode($summary) }}">
                                                                    <span class="material-icons" aria-hidden="true">content_copy</span>
                                                                </button>
                                                            @endif
                                                        </div>
                                                        <div class="card-body voice-call-summary-body">
                                                            @if($summary !== '')
                                                                <p class="text-muted mb-0 small">{{ $summary }}</p>
                                                            @else
                                                                <p class="text-muted mb-0 small">{{ translate('No_call_summary_available') }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-9 d-flex">
                                                <div class="card voice-call-detail-box shadow-sm flex-grow-1 voice-call-extracted-card">
                                                    <div class="card-header voice-call-detail-box__header">
                                                        <div class="voice-call-detail-box__header-title">
                                                            <span class="material-icons" aria-hidden="true">data_object</span>
                                                            <span>{{ translate('Extracted_Data') }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1">
                                                            @if($hasEmptyExtracted)
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-secondary voice-call-extracted-view-all">
                                                                    {{ translate('view_all') }}
                                                                </button>
                                                            @endif
                                                            @if($extractedCopyText !== '')
                                                                <button type="button"
                                                                        class="voice-call-copy-btn"
                                                                        title="{{ translate('Copy') }}"
                                                                        data-copy-b64="{{ base64_encode($extractedCopyText) }}">
                                                                    <span class="material-icons" aria-hidden="true">content_copy</span>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="card-body voice-call-extracted-body">
                                                        @if($extracted !== [])
                                                            <div class="voice-call-extracted-grid">
                                                                @foreach($extracted as $varKey => $varValue)
                                                                    @php
                                                                        $isFilledExtracted = $extractedIsFilled($varValue);
                                                                    @endphp
                                                                    <div class="voice-call-extracted-item {{ $isFilledExtracted ? '' : 'voice-call-extracted-item--empty' }}">
                                                                        <div class="voice-call-extracted-item__label">
                                                                            {{ str_replace('_', ' ', ucfirst($varKey)) }}
                                                                        </div>
                                                                        <div class="voice-call-extracted-item__value">
                                                                            {{ $isFilledExtracted ? $varValue : '—' }}
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <p class="text-muted mb-0 small">{{ translate('No_extracted_data_available') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card voice-call-detail-box shadow-sm">
                                            <div class="card-header voice-call-detail-box__header">
                                                <div class="voice-call-detail-box__header-title">
                                                    <span class="material-icons" aria-hidden="true">forum</span>
                                                    <span>{{ translate('Transcript') }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    @if($transcriptHasDevanagari)
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-secondary voice-call-transcript-hinglish-toggle"
                                                                data-call-id="{{ $call['id'] }}"
                                                                data-original-b64="{{ base64_encode($transcript) }}"
                                                                data-showing="original">
                                                            {{ translate('Show_Hinglish') }}
                                                        </button>
                                                    @endif
                                                    @if($transcript !== '')
                                                        <button type="button"
                                                                class="voice-call-copy-btn voice-call-transcript-copy-btn"
                                                                title="{{ translate('Copy') }}"
                                                                data-copy-b64="{{ base64_encode($transcript) }}">
                                                            <span class="material-icons" aria-hidden="true">content_copy</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="card-body p-0">
                                                @if($transcript !== '')
                                                    <div class="voice-call-transcript"
                                                         data-call-id="{{ $call['id'] }}"
                                                         data-original-b64="{{ base64_encode($transcript) }}"
                                                         @if($storedTransliteration !== '')
                                                             data-transliterated-b64="{{ base64_encode($storedTransliteration) }}"
                                                         @endif>
                                                        @foreach($transcriptLines as $line)
                                                            @php
                                                                $trimmedLine = trim((string) $line);
                                                                $lineClass = '';
                                                                if (stripos($trimmedLine, 'User:') === 0) {
                                                                    $lineClass = 'voice-call-transcript-line--user';
                                                                } elseif (stripos($trimmedLine, 'LLM:') === 0) {
                                                                    $lineClass = 'voice-call-transcript-line--llm';
                                                                }
                                                            @endphp
                                                            @if($trimmedLine !== '')
                                                                <div class="voice-call-transcript-line {{ $lineClass }}">{{ $trimmedLine }}</div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="p-3">
                                                        <p class="text-muted mb-0 small">{{ translate('No_transcript_available') }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
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
