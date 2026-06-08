@php
    $campaign = is_array($campaign ?? null) ? $campaign : [];
    $calls = is_array($calls ?? null) ? $calls : [];
    $callsColspan = 8;
    $statusClass = match ($campaign['status'] ?? '') {
        'completed' => 'success',
        'running', 'pending', 'in_progress' => 'primary',
        'scheduled' => 'info',
        'paused' => 'warning',
        'failed', 'cancelled' => 'danger',
        default => 'secondary',
    };
    $formatDuration = static function (int $seconds): string {
        if ($seconds <= 0) {
            return '—';
        }
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return $minutes . ':' . str_pad((string) $remaining, 2, '0', STR_PAD_LEFT);
    };
    $statusCounts = is_array($campaign['call_status_counts'] ?? null) ? $campaign['call_status_counts'] : [];
@endphp

<div class="voice-bulk-detail">
    @if(!empty($detailsError))
        <div class="alert alert-danger m-3 mb-0">
            {{ translate('Voice_bulk_campaign_details_load_failed') }}
            <span class="d-block small text-muted mt-1">{{ $detailsError }}</span>
        </div>
    @elseif($campaign === [])
        <div class="alert alert-warning m-3 mb-0">{{ translate('no_data_found') }}</div>
    @else
        <div class="voice-bulk-detail__header">
            <div class="voice-bulk-detail__heading">
                <div class="voice-bulk-detail__title">{{ $campaign['name'] ?: translate('Campaign_name') }}</div>
                <div class="voice-bulk-detail__sub">
                    #{{ $campaignId }}
                    @if(!empty($campaign['create_date']))
                        · {{ $campaign['create_date'] }}
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="badge bg-{{ $statusClass }} text-capitalize voice-bulk-detail__status">
                    {{ $campaign['status'] ?: '—' }}
                </span>
                @can('lead_outbound_enquiry_add')
                    @if(!empty($canCancelCampaign))
                        <button type="button"
                                class="btn btn-sm btn-outline-danger voice-bulk-campaign-cancel-btn"
                                data-campaign-id="{{ $campaignId }}"
                                data-campaign-name="{{ $campaign['name'] ?: translate('Campaign_name') }}">
                            {{ translate('Voice_bulk_cancel_campaign') }}
                        </button>
                    @endif
                @endcan
            </div>
        </div>

        <div class="voice-bulk-detail__body">
            <div class="voice-bulk-detail__section">
                <div class="voice-bulk-detail__section-title">{{ translate('Voice_bulk_campaign_setup') }}</div>
                <div class="voice-bulk-detail__grid voice-bulk-detail__grid--3">
                    <div class="voice-bulk-detail__field">
                        <span class="voice-bulk-detail__label">{{ translate('OmniDimension_Agent') }}</span>
                        <span class="voice-bulk-detail__value">{{ $campaign['bot_name'] ?: '—' }}</span>
                    </div>
                    <div class="voice-bulk-detail__field">
                        <span class="voice-bulk-detail__label">{{ translate('Caller_Phone_Number') }}</span>
                        <span class="voice-bulk-detail__value text-nowrap">{{ $campaign['twilio_number'] ?: '—' }}</span>
                    </div>
                    <div class="voice-bulk-detail__field">
                        <span class="voice-bulk-detail__label">{{ translate('Concurrent_Limit') }}</span>
                        <span class="voice-bulk-detail__value">{{ (int) ($campaign['concurrent_call_limit'] ?? 1) }}</span>
                    </div>
                </div>
            </div>

            <div class="voice-bulk-detail__section">
                <div class="voice-bulk-detail__section-title">{{ translate('Voice_bulk_campaign_progress') }}</div>
                <div class="voice-bulk-detail__metrics">
                    <div class="voice-bulk-detail__metric">
                        <span class="voice-bulk-detail__metric-value">{{ (int) ($campaign['total_calls'] ?? 0) }}</span>
                        <span class="voice-bulk-detail__metric-label">{{ translate('Total_Calls') }}</span>
                    </div>
                    <div class="voice-bulk-detail__metric voice-bulk-detail__metric--success">
                        <span class="voice-bulk-detail__metric-value">{{ (int) ($campaign['completed_calls'] ?? 0) }}</span>
                        <span class="voice-bulk-detail__metric-label">{{ translate('Completed') }}</span>
                    </div>
                    <div class="voice-bulk-detail__metric voice-bulk-detail__metric--primary">
                        <span class="voice-bulk-detail__metric-value">{{ (int) ($campaign['total_pending_calls'] ?? 0) }}</span>
                        <span class="voice-bulk-detail__metric-label">{{ translate('Pending') }}</span>
                    </div>
                    <div class="voice-bulk-detail__metric voice-bulk-detail__metric--warning">
                        <span class="voice-bulk-detail__metric-value">{{ (int) ($campaign['total_not_reachable_calls'] ?? 0) }}</span>
                        <span class="voice-bulk-detail__metric-label">{{ translate('Voice_bulk_not_reachable') }}</span>
                    </div>
                    <div class="voice-bulk-detail__metric">
                        <span class="voice-bulk-detail__metric-value">{{ (int) ($campaign['calls_picked_up'] ?? 0) }}</span>
                        <span class="voice-bulk-detail__metric-label">{{ translate('Voice_bulk_calls_picked_up') }}</span>
                    </div>
                    @if((int) ($campaign['total_duration_seconds'] ?? 0) > 0 || (int) ($campaign['avg_duration_seconds'] ?? 0) > 0)
                        <div class="voice-bulk-detail__metric">
                            <span class="voice-bulk-detail__metric-value">{{ $formatDuration((int) ($campaign['total_duration_seconds'] ?? 0)) }}</span>
                            <span class="voice-bulk-detail__metric-label">{{ translate('Duration') }}</span>
                        </div>
                        @if((int) ($campaign['avg_duration_seconds'] ?? 0) > 0)
                            <div class="voice-bulk-detail__metric">
                                <span class="voice-bulk-detail__metric-value">{{ $formatDuration((int) $campaign['avg_duration_seconds']) }}</span>
                                <span class="voice-bulk-detail__metric-label">{{ translate('Voice_bulk_avg_duration') }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            @if(!empty($campaign['is_scheduled']) && !empty($campaign['scheduled_datetime']) || !empty($campaign['auto_retry']) || !empty($campaign['enabled_reschedule_call']) || $statusCounts !== [])
                <div class="voice-bulk-detail__section voice-bulk-detail__section--flush">
                    <div class="voice-bulk-detail__tags">
                        @if(!empty($campaign['is_scheduled']) && !empty($campaign['scheduled_datetime']))
                            <span class="voice-bulk-detail__tag voice-bulk-detail__tag--schedule">
                                <span class="material-icons" aria-hidden="true">schedule</span>
                                {{ translate('Schedule') }}: {{ $campaign['scheduled_datetime'] }}
                                @if(!empty($campaign['timezone']))
                                    ({{ $campaign['timezone'] }})
                                @endif
                            </span>
                        @endif
                        @if(!empty($campaign['auto_retry']))
                            <span class="voice-bulk-detail__tag">
                                {{ translate('Enable_auto_retry') }}
                                @if(!empty($campaign['auto_retry_schedule']))
                                    · {{ str_replace('_', ' ', $campaign['auto_retry_schedule']) }}
                                @endif
                                @if((int) ($campaign['retry_limit'] ?? 0) > 0)
                                    · {{ translate('Retry_Limit') }} {{ (int) $campaign['retry_limit'] }}
                                @endif
                            </span>
                        @endif
                        @if(!empty($campaign['enabled_reschedule_call']))
                            <span class="voice-bulk-detail__tag">{{ translate('Enable_call_rescheduling') }}</span>
                        @endif
                        @foreach($statusCounts as $statusKey => $count)
                            @if((int) $count > 0)
                                <span class="voice-bulk-detail__tag voice-bulk-detail__tag--muted text-capitalize">
                                    {{ $statusKey }}: {{ (int) $count }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($campaign['failed_reason']))
                <div class="alert alert-danger py-2 px-3 mb-0 mx-3 mt-0 small">{{ $campaign['failed_reason'] }}</div>
            @endif

            <div class="voice-bulk-detail__calls">
                <div class="voice-bulk-detail__calls-head">
                    <div>
                        <div class="voice-bulk-detail__calls-title">{{ translate('Voice_bulk_campaign_calls_title') }}</div>
                        <div class="voice-bulk-detail__calls-hint">{{ translate('Voice_bulk_campaign_calls_hint') }}</div>
                    </div>
                    <span class="badge bg-light text-dark border">{{ (int) ($callsTotal ?? count($calls)) }} {{ translate('Total_Calls') }}</span>
                </div>

                @if(!empty($callsError))
                    <div class="alert alert-warning mx-3 mt-3 mb-0 small">
                        {{ translate('OmniDimension_call_history_failed') }}
                        <span class="d-block text-muted mt-1">{{ $callsError }}</span>
                    </div>
                @endif

                <div class="voice-bulk-detail__calls-wrap">
                    <table class="table table-sm table-hover align-middle mb-0 voice-bulk-detail__calls-table voice-call-history-table">
                        <thead>
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Date_Time') }}</th>
                            <th>{{ translate('Customer_Name') }}</th>
                            <th>{{ translate('Phone_Number') }}</th>
                            <th>{{ translate('Call_Reason') }}</th>
                            <th>{{ translate('Call_Status') }}</th>
                            <th>{{ translate('Duration') }}</th>
                            <th>{{ translate('Details') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($calls as $key => $call)
                            @php
                                $dispatchContext = is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [];
                                $extracted = is_array($call['extracted_variables'] ?? null) ? $call['extracted_variables'] : [];
                                $transcript = trim((string) ($call['transcript'] ?? ''));
                                $summary = trim((string) ($call['sentiment_analysis_details'] ?? ''));
                                $hasRecording = !empty($call['recording_url']) && (int) ($call['id'] ?? 0) > 0;
                                $hasDetails = $hasRecording || $transcript !== '' || $extracted !== [] || $summary !== '' || $dispatchContext !== [];
                                $contextCustomer = trim((string) ($dispatchContext['customer_name'] ?? ''));
                                $contextReason = trim((string) ($dispatchContext['call_reason'] ?? ''));
                                $contextReasonLabel = ($callReasonLabels ?? [])[$contextReason] ?? $contextReason;
                                $statusBadge = match (strtolower((string) ($call['call_status'] ?? ''))) {
                                    'completed' => 'success',
                                    'failed', 'busy', 'no-answer' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr data-call-log-id="{{ $call['id'] }}">
                                <td>{{ (($callsPage ?? 1) - 1) * pagination_limit() + $key + 1 }}</td>
                                <td class="text-nowrap">{{ $call['time_of_call'] ?: '—' }}</td>
                                <td class="voice-bulk-detail__calls-name">{{ $contextCustomer !== '' ? $contextCustomer : '—' }}</td>
                                <td class="text-nowrap">{{ $call['to_number'] ?: '—' }}</td>
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
                                <td>
                                    <span class="badge bg-{{ $statusBadge }} text-capitalize">{{ $call['call_status'] ?: '—' }}</span>
                                </td>
                                <td class="text-nowrap">{{ $call['call_duration'] ?: '—' }}</td>
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
                                    <td colspan="{{ $callsColspan }}" class="p-0 border-0">
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
                                <td colspan="{{ $callsColspan }}" class="text-center text-muted py-4">
                                    {{ translate('Voice_bulk_campaign_calls_empty') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($callsTotal > pagination_limit())
                    @php $totalPages = (int) ceil($callsTotal / pagination_limit()); @endphp
                    <nav class="voice-bulk-detail__calls-pagination">
                        <ul class="pagination pagination-sm mb-0">
                            @for($p = 1; $p <= $totalPages; $p++)
                                <li class="page-item {{ $p === ($callsPage ?? 1) ? 'active' : '' }}">
                                    <a class="page-link voice-bulk-campaign-calls-page-link"
                                       href="#"
                                       data-campaign-id="{{ $campaignId }}"
                                       data-page="{{ $p }}">{{ $p }}</a>
                                </li>
                            @endfor
                        </ul>
                    </nav>
                @endif
            </div>
        </div>
    @endif
</div>
