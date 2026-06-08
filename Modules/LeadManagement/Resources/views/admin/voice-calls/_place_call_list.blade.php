@php
    $listRoute = $listRoute ?? route('admin.voice-call.placed');
    $callLogsByRequestId = $callLogsByRequestId ?? [];
    $placedColspan = 9;
@endphp

@if(!empty($statusLoadError))
    <div class="alert alert-warning mb-3">
        {{ translate('Voice_placed_calls_status_load_failed') }}
        <span class="d-block small text-muted mt-1">{{ $statusLoadError }}</span>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $listRoute }}" id="voice-placed-filter-form" class="voice-call-filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    @include('leadmanagement::admin.voice-calls._form_field_label', [
                        'label' => translate('search_here'),
                        'hint' => translate('Voice_field_hint_placed_calls_search'),
                    ])
                    <input type="text"
                           class="form-control"
                           name="search"
                           value="{{ $filterSearch ?? '' }}"
                           placeholder="{{ translate('Voice_placed_calls_search_placeholder') }}">
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button class="btn btn--primary" type="submit">{{ translate('Search') }}</button>
                    <button class="btn btn--secondary voice-placed-reset" type="button">{{ translate('Reset') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-30">
        @if($dispatches->isEmpty())
            <div class="text-center text-muted py-4">
                {{ translate('Voice_placed_calls_empty') }}
            </div>
        @else
            <div class="table-responsive voice-call-table-wrap">
                <table class="table table-hover align-middle mb-0 voice-call-data-table voice-placed-calls-table voice-call-history-table">
                    <thead>
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Customer_Name') }}</th>
                        <th>{{ translate('Phone_Number') }}</th>
                        <th>{{ translate('Call_Reason') }}</th>
                        <th>{{ translate('Request_ID') }}</th>
                        <th>{{ translate('Call_Status') }}</th>
                        <th>{{ translate('Placed_By') }}</th>
                        <th>{{ translate('Details') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($dispatches as $index => $dispatch)
                        @php
                            $rowNum = ($dispatches->currentPage() - 1) * $dispatches->perPage() + $index + 1;
                            $context = is_array($dispatch->call_context) ? $dispatch->call_context : [];
                            $dispatchContext = $dispatch->normalizedContext();
                            $customerName = trim((string) ($context['customer_name'] ?? ''));
                            $callReason = trim((string) ($context['call_reason'] ?? ''));
                            $callReasonLabel = ($callReasonLabels ?? [])[$callReason] ?? $callReason;
                            $placedBy = $dispatch->dispatchedBy;
                            $placedByName = $placedBy
                                ? trim(($placedBy->first_name ?? '') . ' ' . ($placedBy->last_name ?? '')) ?: ($placedBy->email ?? '—')
                                : '—';
                            $requestId = (int) ($dispatch->omnidim_request_id ?? 0);
                            $matchedCall = $requestId > 0 ? ($callLogsByRequestId[$requestId] ?? null) : null;
                            $call = is_array($matchedCall) ? $matchedCall : [
                                'id' => 0,
                                'recording_url' => null,
                                'transcript' => '',
                                'sentiment_analysis_details' => '',
                                'extracted_variables' => [],
                                'transcript_transliterated' => '',
                            ];
                            if (is_array($matchedCall) && $dispatchContext !== []) {
                                $call['dispatch_context'] = $dispatchContext;
                            }
                            $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
                            $transcript = trim((string) ($call['transcript'] ?? ''));
                            $summary = trim((string) ($call['sentiment_analysis_details'] ?? ''));
                            $hasRecording = !empty($call['recording_url']) && (int) ($call['id'] ?? 0) > 0;
                            $hasDetails = $hasRecording || $transcript !== '' || $extracted !== [] || $summary !== '' || $dispatchContext !== [];
                            $liveStatus = is_array($matchedCall) ? trim((string) ($matchedCall['call_status'] ?? '')) : '';
                            $dispatchStatus = trim((string) ($dispatch->dispatch_status ?? ''));
                            $displayStatus = $liveStatus !== '' ? $liveStatus : ($dispatchStatus !== '' ? $dispatchStatus : '—');
                            $statusBadge = match (strtolower($displayStatus)) {
                                'completed' => 'success',
                                'failed', 'busy', 'no-answer' => 'danger',
                                'dispatched', 'pending', 'ringing', 'in-progress', 'ongoing' => 'warning',
                                default => 'secondary',
                            };
                        @endphp
                        <tr data-dispatch-id="{{ $dispatch->id }}">
                            <td>{{ $rowNum }}</td>
                            <td class="text-nowrap small">{{ $dispatch->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $customerName !== '' ? $customerName : '—' }}</td>
                            <td class="text-nowrap">{{ $dispatch->to_number_e164 ?? '—' }}</td>
                            <td class="small">
                                @if($callReasonLabel !== '')
                                    @include('leadmanagement::admin.voice-calls._call_reason_badge', [
                                        'reason' => $callReason,
                                        'label' => $callReasonLabel,
                                        'callReasonLabels' => $callReasonLabels ?? [],
                                    ])
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($requestId > 0)
                                    <span class="badge bg-light text-dark border">#{{ $requestId }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($displayStatus !== '—')
                                    <span class="badge bg-{{ $statusBadge }} text-capitalize">{{ $displayStatus }}</span>
                                    @if(is_array($matchedCall) && !empty($matchedCall['call_duration']))
                                        <div class="small text-muted mt-1">{{ $matchedCall['call_duration'] }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $placedByName }}</td>
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
                        </tr>
                        @if($hasDetails)
                            <tr class="voice-call-details-row d-none">
                                <td colspan="{{ $placedColspan }}" class="p-0 border-0">
                                    @include('leadmanagement::admin.voice-calls._call_log_details_panel', [
                                        'call' => $call,
                                        'dispatchContext' => $dispatchContext,
                                        'callReasonLabels' => $callReasonLabels ?? [],
                                    ])
                                </td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($dispatches->hasPages())
                <div class="d-flex justify-content-end mt-3">
                    <nav>
                        <ul class="pagination mb-0">
                            @if($dispatches->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                            @else
                                <li class="page-item">
                                    <a class="page-link voice-placed-page-link" href="#" data-page="{{ $dispatches->currentPage() - 1 }}">&laquo;</a>
                                </li>
                            @endif

                            @for($p = max(1, $dispatches->currentPage() - 2); $p <= min($dispatches->lastPage(), $dispatches->currentPage() + 2); $p++)
                                <li class="page-item {{ $p === $dispatches->currentPage() ? 'active' : '' }}">
                                    <a class="page-link voice-placed-page-link" href="#" data-page="{{ $p }}">{{ $p }}</a>
                                </li>
                            @endfor

                            @if($dispatches->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link voice-placed-page-link" href="#" data-page="{{ $dispatches->currentPage() + 1 }}">&raquo;</a>
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
