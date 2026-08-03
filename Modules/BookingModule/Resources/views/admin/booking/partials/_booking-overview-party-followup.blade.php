@php
    $followup = $followup ?? null;
    $partyMeta = $partyMeta ?? null;
    $followupToneClass = '';
    if (!empty($partyMeta['has_pending'])) {
        $followupToneClass = !empty($partyMeta['is_overdue']) ? 'party-line--followup-missed' : 'party-line--followup-due';
    }
@endphp
<div class="party-line party-line--followup {{ $followupToneClass }}">
    <span class="material-icons party-line__icon" aria-hidden="true">event</span>
    <div class="party-followup">
        <div class="party-followup__line">
            <span class="party-followup__label">{{ translate('Next_Follow_up') }}:</span>
            @if($followup && $followup->date)
                <span class="followup-value__when {{ !empty($partyMeta['has_pending']) ? (!empty($partyMeta['is_overdue']) ? 'text-danger' : 'text-warning') : '' }}">
                    {{ $followup->date->format('d-M-Y h:ia') }}
                </span>
                @if(!empty($partyMeta['has_pending']))
                    <span class="badge rounded-pill {{ !empty($partyMeta['is_overdue']) ? 'bg-danger' : 'bg-warning text-dark' }}">
                        {{ !empty($partyMeta['is_overdue']) ? translate('Missed') : translate('Pending') }}
                    </span>
                @elseif(!empty($partyMeta['badge']))
                    <span class="badge rounded-pill {{ $partyMeta['badge']['badge_class'] }}">
                        {{ translate($partyMeta['badge']['label']) }}
                    </span>
                @endif
            @else
                <span class="followup-value__when text-muted fw-normal">—</span>
            @endif
        </div>
        @if($followup?->reason)
            <div class="party-followup__remark">{{ Str::limit($followup->reason, 80) }}</div>
        @endif
    </div>
</div>
