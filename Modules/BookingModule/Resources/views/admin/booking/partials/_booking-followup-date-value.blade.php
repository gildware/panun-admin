@php
    $partyMeta = $partyMeta ?? null;
    $followup = $followup ?? null;
@endphp
@if($followup && $followup->date)
    <span class="{{ !empty($partyMeta['has_pending']) ? (!empty($partyMeta['is_overdue']) ? 'text-danger' : 'text-warning') : '' }}">
        {{ $followup->date->format('d-M-Y h:ia') }}
        @if(!empty($partyMeta['has_pending']))
            <span class="badge rounded-pill {{ !empty($partyMeta['is_overdue']) ? 'bg-danger' : 'bg-warning text-dark' }} ms-1">
                {{ !empty($partyMeta['is_overdue']) ? translate('Missed') : translate('Pending') }}
            </span>
        @elseif(!empty($partyMeta['badge']))
            <span class="badge rounded-pill {{ $partyMeta['badge']['badge_class'] }} ms-1">
                {{ translate($partyMeta['badge']['label']) }}
            </span>
        @endif
        @if($followup->reason)
            <span class="text-muted fw-normal"> ({{ Str::limit($followup->reason, 60) }})</span>
        @endif
    </span>
@else
    <span class="text-muted fw-normal">—</span>
@endif
