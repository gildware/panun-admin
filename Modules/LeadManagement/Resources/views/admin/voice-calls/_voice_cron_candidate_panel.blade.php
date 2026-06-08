@php
    use Modules\LeadManagement\Entities\Lead;

    $leadTypes = Lead::leadTypes();
    $type = (string) ($candidate['lead_type'] ?? '');
    $typeLabel = $leadTypes[$type] ?? ($type !== '' ? ucfirst($type) : translate('Unknown'));
    $callContext = is_array($candidate['call_context'] ?? null) ? $candidate['call_context'] : [];
    $phone = (string) ($candidate['phone'] ?? '');
    $showHeader = $showHeader ?? true;
    $compact = $compact ?? false;
@endphp

<div @class(['voice-cron-candidate-panel', 'border rounded bg-white mb-2 overflow-hidden' => !$compact])>
    @if($showHeader)
        <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 bg-light border-bottom">
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate">{{ $candidate['display_name'] ?? '—' }}</div>
                <div class="small text-muted">{{ $phone ?: '—' }}</div>
            </div>
            <span class="badge bg-light text-dark">{{ $typeLabel }}</span>
            @if(!empty($candidate['lead_status_label']))
                <span class="badge rounded-pill {{ $candidate['lead_status_badge'] ?? 'bg-secondary' }}">
                    {{ translate($candidate['lead_status_label']) }}
                </span>
            @endif
        </div>
    @endif

    <div class="px-3 py-2 border-bottom">
        <div class="row g-2 small">
            <div class="col-sm-6 col-lg-4">
                <span class="text-muted">{{ translate('Not_replied_since') }}:</span>
                <span class="ms-1">{{ $candidate['silent_since_label'] ?? '—' }}</span>
                @if(!empty($candidate['silent_duration_label']))
                    <span class="text-muted">({{ $candidate['silent_duration_label'] }})</span>
                @endif
            </div>
            <div class="col-sm-6 col-lg-4">
                <span class="text-muted">{{ translate('Handled_By') }}:</span>
                <span class="ms-1">{{ $candidate['handled_by_label'] ?? ($candidate['handled_by'] ?? '—') }}</span>
            </div>
            @if(!empty($candidate['last_followup_at_label']))
                <div class="col-sm-6 col-lg-4">
                    <span class="text-muted">{{ translate('Last_followup_call_on') }}:</span>
                    <span class="ms-1">{{ $candidate['last_followup_at_label'] }}</span>
                </div>
            @endif
            @if(!empty($candidate['chat_tags']))
                <div class="col-12">
                    <span class="text-muted">{{ translate('whatsapp_chat_tags_label') }}:</span>
                    <span class="ms-1">
                        @foreach($candidate['chat_tags'] as $tag)
                            <span class="badge" style="background-color:{{ $tag['color'] ?: '#6c757d' }};color:#fff;">{{ $tag['name'] }}</span>
                        @endforeach
                    </span>
                </div>
            @endif
            @if(!empty($candidate['customer_lead_tags']))
                <div class="col-12">
                    <span class="text-muted">{{ translate('Customer_Lead_Tags') }}:</span>
                    <span class="ms-1">
                        @foreach($candidate['customer_lead_tags'] as $tag)
                            <span class="badge" style="background-color:{{ is_array($tag) ? (($tag['color'] ?? '') ?: '#6c757d') : '#6c757d' }};color:#fff;">
                                {{ is_array($tag) ? ($tag['name'] ?? '') : $tag }}
                            </span>
                        @endforeach
                    </span>
                </div>
            @endif
            @if(!empty($candidate['last_ai_message_preview']))
                <div class="col-12">
                    <span class="text-muted">{{ translate('Last_AI_message') }}:</span>
                    <span class="ms-1">{{ $candidate['last_ai_message_preview'] }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="p-2">
        @include('leadmanagement::admin.voice-calls._whatsapp_followup_call_context', [
            'phone' => $phone,
            'callContext' => $callContext,
            'callReasonLabels' => $callReasonLabels ?? [],
            'contextKeys' => $contextKeys ?? \Modules\LeadManagement\Services\OutboundCallContextService::CONTEXT_KEYS,
            'needsRefresh' => !empty($candidate['cached_summary_needs_refresh']),
            'summaryActionTitle' => translate('Generate_summary'),
        ])
    </div>
</div>
