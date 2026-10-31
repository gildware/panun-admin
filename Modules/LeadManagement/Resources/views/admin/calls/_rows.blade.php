@foreach($calls as $call)
    @php
        $contactMark = $contactMarks[$call->id] ?? null;
        $needsCallback = isset($awaitingCallbackIds[$call->id]) && $contactMark === null;
    @endphp
    <tr @class([
        'calls-row--needs-callback' => $needsCallback,
        'calls-row--contacted' => $contactMark && $contactMark->channel !== 'not_picked',
        'calls-row--not-picked' => $contactMark && $contactMark->channel === 'not_picked',
    ]) @if($needsCallback) title="{{ translate('Needs_callback') }}" @elseif($contactMark) title="{{ $contactMark->channelLabel() }}" @endif>
        <td>
            <a href="{{ route('admin.calls.show', $call) }}">
                {{ $call->formatIst('started_at') }}
            </a>
        </td>
        <td class="calls-call-id-cell">
            @if($call->cdr_uuid)
                <button type="button"
                        class="btn btn-outline-secondary calls-copy-id"
                        data-call-id="{{ $call->cdr_uuid }}"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="{{ translate('Copy_call_id') }}"
                        title="{{ translate('Copy_call_id') }}"
                        aria-label="{{ translate('Copy_call_id') }}">
                    <span class="material-icons" aria-hidden="true">content_copy</span>
                </button>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td>
            @if($call->direction === 'INBOUND')
                <span class="call-pill call-pill--in">
                    <span class="material-icons">call_received</span>
                    {{ translate('Inbound') }}
                </span>
            @elseif($call->direction === 'OUTBOUND')
                <span class="call-pill call-pill--out">
                    <span class="material-icons">call_made</span>
                    {{ translate('Outbound') }}
                </span>
            @else
                {{ $call->directionLabel() }}
            @endif
        </td>
        <td title="{{ $call->customer_phone }}">{{ $call->customer_phone ?: '—' }}</td>
        <td title="{{ $call->staff_name }}">{{ $call->staff_name ?: '—' }}</td>
        <td class="calls-result-cell">
            @php
                $outcome = $call->outcome ?: 'unknown';
                if ($outcome === 'missed' && $call->direction === 'OUTBOUND') {
                    $outcome = 'unanswered';
                }
                $outcomeIcon = match ($outcome) {
                    'answered' => 'call',
                    'missed' => 'phone_missed',
                    'unanswered' => 'call_end',
                    'other' => 'phone_disabled',
                    default => 'help_outline',
                };
                $outcomeClass = match ($outcome) {
                    'answered' => 'call-pill--answered',
                    'missed' => 'call-pill--missed',
                    'unanswered' => 'call-pill--unanswered',
                    'other' => 'call-pill--other',
                    default => 'call-pill--unknown',
                };
            @endphp
            <span class="calls-result-pills">
                <span class="call-pill {{ $outcomeClass }}">
                    <span class="material-icons">{{ $outcomeIcon }}</span>
                    {{ $call->outcomeLabel() }}
                </span>
                @if($needsCallback)
                    <span class="call-pill call-pill--needs-callback">
                        <span class="material-icons">phone_callback</span>
                        {{ translate('Needs_callback') }}
                    </span>
                @elseif($contactMark)
                    <span class="call-pill call-pill--contact-{{ $contactMark->channel }}">
                        <span class="material-icons">{{ $contactMark->channelIcon() }}</span>
                        {{ $contactMark->channelLabel() }}
                    </span>
                @endif
            </span>
        </td>
        <td>{{ $call->durationLabel() }}</td>
        <td>
            @if($call->hasRecording())
                <button type="button"
                        class="call-pill call-pill--rec calls-play-recording"
                        title="{{ translate('Play_recording') }}"
                        data-recording-url="{{ route('admin.calls.recording', $call) }}"
                        data-recording-title="{{ $call->customer_phone ?: translate('Recording') }}">
                    <span class="material-icons">mic</span>
                </button>
            @elseif($call->summary_text)
                <span class="call-pill call-pill--note" title="{{ translate('Summary') }}">
                    <span class="material-icons">notes</span>
                </span>
            @else
                —
            @endif
        </td>
        <td>
            @php
                $partyProvider = trim((string) ($call->provider->company_name ?? $call->provider->contact_person_name ?? ''));
                $partyCustomer = trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
            @endphp
            @if($call->provider)
                <span class="party-pill party-pill--provider" title="{{ $partyProvider }}">{{ translate('Provider') }}{{ $partyProvider !== '' ? ' · '.$partyProvider : '' }}</span>
            @elseif($call->customer)
                <span class="party-pill party-pill--customer" title="{{ $partyCustomer }}">{{ translate('Customer') }}{{ $partyCustomer !== '' ? ' · '.$partyCustomer : '' }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="calls-lead-cell">
            @include('leadmanagement::admin.calls._lead_cell', [
                'call' => $call,
                'openBookings' => $bookingChoices[$call->id] ?? collect(),
                'rowsOnly' => $rowsOnly ?? false,
            ])
        </td>
        <td class="calls-booking-cell">
            @if($call->booking)
                @php $bookingStatus = (string) ($call->booking->booking_status ?: 'pending'); @endphp
                <a class="booking-chip booking-chip--{{ $bookingStatus }}" href="{{ route('admin.booking.details', [$call->booking_id, 'web_page' => 'details']) }}">
                    <span class="booking-chip__id">{{ $call->booking->readable_id ?: translate('Booking') }}</span>
                    <span class="booking-chip__status">{{ translate(ucfirst(str_replace('_', ' ', $bookingStatus))) }}</span>
                </a>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="calls-actions-cell">
            <div class="calls-actions-stack">
            @if($needsCallback)
                <button type="button"
                        class="btn btn-outline-danger calls-contact-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#call-contact-modal"
                        data-contact-action="{{ route('admin.calls.contact', $call) }}"
                        data-contact-phone="{{ $call->customer_phone }}">
                    {{ translate('Mark_contacted') }}
                </button>
            @endif
            @if(!$call->lead && !$call->booking)
                @php
                    $linkProvider = trim((string) ($call->provider->company_name ?? $call->provider->contact_person_name ?? ''));
                    $linkCustomer = trim((string) (($call->customer->first_name ?? '').' '.($call->customer->last_name ?? '')));
                    if ($call->provider) {
                        $linkParty = 'provider';
                        $linkPartyName = $linkProvider !== '' ? $linkProvider : translate('Provider');
                    } elseif ($call->customer) {
                        $linkParty = 'customer';
                        $linkPartyName = $linkCustomer !== '' ? $linkCustomer : translate('Customer');
                    } else {
                        $linkParty = 'none';
                        $linkPartyName = '';
                    }
                    $reopenLead = (($reopenLeadByCall ?? [])[$call->id] ?? null);
                @endphp
                <button type="button"
                        class="btn btn--primary calls-link-lead-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#call-link-lead-modal"
                        data-phone="{{ $call->customer_phone }}"
                        data-party="{{ $linkParty }}"
                        data-party-name="{{ $linkPartyName }}"
                        data-options-url="{{ route('admin.calls.link-options', $call) }}"
                        data-lead-action="{{ route('admin.calls.link', $call) }}"
                        data-booking-action="{{ route('admin.calls.booking', $call) }}">
                    {{ translate('Link_call') }}
                </button>
                @if($reopenLead)
                    @can('lead_update')
                        <form method="post" action="{{ route('admin.calls.reopen-link', $call) }}" class="d-inline" onsubmit="return confirm(@json(translate('Reopen_non_responsive_lead_and_link_call')));">
                            @csrf
                            <input type="hidden" name="lead_id" value="{{ $reopenLead->id }}">
                            <button type="submit" class="btn btn-outline-secondary calls-link-lead-btn">{{ translate('Reopen') }} #{{ $reopenLead->id }}</button>
                        </form>
                    @endcan
                @endif
            @else
                <form method="post" action="{{ route('admin.calls.unmatch', $call) }}" class="d-inline" onsubmit="return confirm(@json(translate('Unmatch_this_call')));">
                    @csrf
                    <button type="submit" class="btn btn--danger calls-unmatch-btn">{{ translate('Unmatch') }}</button>
                </form>
            @endif
            @can('lead_add')
                @if(strlen((string) ($call->customer_phone_digits ?: '')) >= 8)
                    <form method="post" action="{{ route('admin.calls.create-lead', $call) }}" class="d-inline" onsubmit="return confirm(@json(translate('Create_new_lead_from_this_call_confirm')));">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary calls-link-lead-btn">{{ translate('New_lead') }}</button>
                    </form>
                @endif
            @endcan
            </div>
        </td>
    </tr>
@endforeach
