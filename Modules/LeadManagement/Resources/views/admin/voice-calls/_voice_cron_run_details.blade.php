@php
    use Modules\LeadManagement\Entities\Lead;

    $leadTypes = Lead::leadTypes();
    $isPending = $run->isPendingApproval();
    $hasCalls = !empty($calls);
    $callsPending = $isPending && !$hasCalls && (int) $run->contacts_dispatched === 0;
@endphp

<div class="voice-cron-run-details-panel border rounded bg-white p-3 mt-2" data-run-id="{{ $run->id }}">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="mb-0">{{ translate('Voice_cron_calls_made_title') }}</h6>
        <button type="button" class="btn btn-sm btn-outline-secondary voice-cron-run-details-close">
            {{ translate('Close') }}
        </button>
    </div>

    @if(!empty($statusCounts))
        <div class="d-flex flex-wrap gap-1 mb-2">
            @foreach($statusCounts as $statusKey => $count)
                @php
                    $statusBadge = match ($statusKey) {
                        'completed' => 'success',
                        'failed', 'busy', 'no-answer', 'no_answer' => 'danger',
                        'pending', 'in_progress', 'running' => 'primary',
                        default => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $statusBadge }} text-capitalize">{{ $statusKey }}: {{ $count }}</span>
            @endforeach
        </div>
    @endif

    @if(!empty($callsError))
        <div class="alert alert-warning py-2 small mb-2">
            {{ translate('OmniDimension_call_history_failed') }}
            <span class="d-block text-muted mt-1">{{ $callsError }}</span>
        </div>
    @endif

    @if($hasCalls)
        <div class="table-responsive voice-call-table-wrap" style="max-height:420px; overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0 voice-call-data-table">
                <thead class="table-light sticky-top">
                <tr>
                    <th>{{ translate('Customer_Name') }}</th>
                    <th>{{ translate('Phone_Number') }}</th>
                    <th>{{ translate('Lead_type') }}</th>
                    <th>{{ translate('Call_Status') }}</th>
                    <th>{{ translate('Duration') }}</th>
                    <th>{{ translate('Campaign') }}</th>
                    <th>{{ translate('Date_Time') }}</th>
                    <th class="text-end">{{ translate('Context') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($calls as $index => $call)
                    @php
                        $dispatchContext = is_array($call['dispatch_context'] ?? null) ? $call['dispatch_context'] : [];
                        $phone = (string) ($call['to_number'] ?? '—');
                        $lead = $call['lead'] ?? null;
                        $type = (string) ($call['lead_type'] ?? '');
                        $typeLabel = $leadTypes[$type] ?? ($type !== '' ? ucfirst($type) : translate('Unknown'));
                        $customerName = trim((string) ($dispatchContext['customer_name'] ?? ''));
                        if ($customerName === '' && $lead) {
                            $customerName = (string) ($lead->name ?: $lead->phone_number);
                        }
                        $status = strtolower((string) ($call['call_status'] ?? ''));
                        $statusBadge = match ($status) {
                            'completed' => 'success',
                            'failed', 'busy', 'no-answer', 'no_answer' => 'danger',
                            'pending', 'in_progress', 'running' => 'primary',
                            default => 'secondary',
                        };
                        $collapseId = 'voice-cron-call-context-' . $run->id . '-' . $index;
                    @endphp
                    <tr>
                        <td class="text-truncate" style="max-width:140px;">{{ $customerName !== '' ? $customerName : '—' }}</td>
                        <td class="text-nowrap small">{{ $phone }}</td>
                        <td><span class="badge bg-light text-dark">{{ $typeLabel }}</span></td>
                        <td>
                            <span class="badge bg-{{ $statusBadge }} text-capitalize">{{ $call['call_status'] ?: '—' }}</span>
                        </td>
                        <td class="small text-nowrap">{{ $call['call_duration'] ?: '—' }}</td>
                        <td class="small text-nowrap">
                            @if(!empty($call['campaign_id']))
                                #{{ $call['campaign_id'] }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="small text-nowrap">{{ $call['time_of_call'] ?: '—' }}</td>
                        <td class="text-end">
                            @if($dispatchContext !== [])
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="false">
                                    {{ translate('View') }}
                                </button>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @if($dispatchContext !== [])
                        <tr>
                            <td colspan="8" class="p-0 border-0 bg-light">
                                <div class="collapse" id="{{ $collapseId }}">
                                    <div class="p-2">
                                        @include('leadmanagement::admin.voice-calls._whatsapp_followup_call_context', [
                                            'phone' => $phone,
                                            'callContext' => $dispatchContext,
                                            'callReasonLabels' => $callReasonLabels ?? [],
                                            'contextKeys' => $contextKeys ?? [],
                                            'needsRefresh' => false,
                                            'summaryActionTitle' => translate('Generate_summary'),
                                        ])
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @elseif($callsPending)
        <p class="text-muted small mb-0">{{ translate('Voice_cron_calls_not_made_yet') }}</p>
    @else
        <p class="text-muted small mb-0">{{ translate('Voice_cron_calls_made_empty') }}</p>
    @endif
</div>
