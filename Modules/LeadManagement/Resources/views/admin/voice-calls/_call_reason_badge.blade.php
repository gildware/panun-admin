@php
    use Modules\LeadManagement\Services\OutboundCallContextService;

    $reasonKey = trim((string) ($reason ?? ''));
    $label = trim((string) ($label ?? ''));
    if ($label === '' && $reasonKey !== '') {
        $label = ($callReasonLabels ?? OutboundCallContextService::callReasonLabels())[$reasonKey] ?? $reasonKey;
    }
@endphp
@if($label !== '')
    <span class="badge voice-call-reason-badge {{ OutboundCallContextService::callReasonBadgeClass($reasonKey) }}">{{ $label }}</span>
@endif
