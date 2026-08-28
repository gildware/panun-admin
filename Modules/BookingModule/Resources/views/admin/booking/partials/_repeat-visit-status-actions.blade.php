@php
    $nextStatuses = $visit['next_statuses'] ?? [];
    $statusUrl = (string) ($visit['status_url'] ?? '');
    $visitStatus = (string) ($visit['booking_status'] ?? '');
    $canMarkOngoing = ! empty($visit['can_mark_ongoing']);
    $hasProvider = ! empty($visit['provider_id']);
    $isCompact = ! empty($compact);
    $hasNextStatuses = count($nextStatuses) > 0;
    $hasStatusUrl = $statusUrl !== '';
@endphp
@if($hasStatusUrl && $hasNextStatuses)
    @can('booking_can_manage_status')
        <div class="repeat-visit-status-actions {{ $isCompact ? 'repeat-visit-status-actions--compact' : '' }}">
            @foreach($nextStatuses as $nextStatus)
                @php
                    $nextStatus = (string) $nextStatus;
                    $btnDisabled = false;
                    $btnTitle = '';
                    if ($nextStatus === 'ongoing' && ! $canMarkOngoing) {
                        $btnDisabled = true;
                        $btnTitle = translate('Booking_ongoing_only_on_or_after_schedule_date');
                    }
                    if (($nextStatus === 'accepted' || $nextStatus === 'ongoing') && ! $hasProvider) {
                        $btnDisabled = true;
                        $btnTitle = translate('Assign_provider_before_accept_or_ongoing');
                    }
                    $btnClass = 'btn-outline-primary';
                    $btnLabel = ucwords(str_replace('_', ' ', $nextStatus));
                    if ($nextStatus === 'accepted') {
                        $btnClass = 'btn-success';
                        $btnLabel = translate('Accept_Booking');
                    } elseif ($nextStatus === 'ongoing') {
                        $btnClass = 'btn-primary';
                        $btnLabel = translate('Mark_as_Ongoing');
                    } elseif ($nextStatus === 'on_hold') {
                        $btnClass = 'btn-outline-warning';
                        $btnLabel = $visitStatus === 'ongoing' ? translate('Hold_after_visit') : translate('Put_on_hold');
                    } elseif ($nextStatus === 'completed') {
                        $btnClass = 'btn-success';
                        $btnLabel = translate('Complete_Booking');
                    } elseif ($nextStatus === 'canceled' || $nextStatus === 'cancelled') {
                        $btnClass = 'btn-outline-danger';
                        $btnLabel = translate('Cancel_Booking');
                    } elseif ($nextStatus === 'pending') {
                        $btnClass = 'btn-outline-primary';
                        $btnLabel = translate('Restore_to_pending');
                    }
                @endphp
                <button type="button"
                        class="btn btn-sm {{ $btnClass }} js-repeat-visit-status-btn"
                        data-status="{{ $nextStatus }}"
                        data-current="{{ $visitStatus }}"
                        data-status-url="{{ $statusUrl }}"
                        @if($btnDisabled) disabled title="{{ $btnTitle }}" @endif>
                    {{ $btnLabel }}
                </button>
            @endforeach
        </div>
    @endcan
@endif
