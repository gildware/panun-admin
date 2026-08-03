@php
    $pipelineSteps = [
        ['key' => 'pending', 'label' => translate('Pending')],
        ['key' => 'accepted', 'label' => translate('Accepted')],
        ['key' => 'ongoing', 'label' => translate('Ongoing')],
        ['key' => 'completed', 'label' => translate('Completed')],
    ];
    $currentStatus = strtolower((string) ($booking->booking_status ?? 'pending'));
    if ($currentStatus === 'on_hold') {
        $currentStatus = 'ongoing';
    }
    if (in_array($currentStatus, ['canceled', 'cancelled', 'refunded', 'pending_cancellation'], true)) {
        $currentIndex = array_search('ongoing', array_column($pipelineSteps, 'key'), true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }
    } else {
        $currentIndex = 0;
        foreach ($pipelineSteps as $i => $step) {
            if ($step['key'] === $currentStatus) {
                $currentIndex = $i;
                break;
            }
        }
    }
    $terminalStatuses = ['canceled', 'cancelled', 'refunded', 'pending_cancellation'];
    $isTerminal = in_array(strtolower((string) ($booking->booking_status ?? '')), $terminalStatuses, true);
@endphp
<div class="booking-pipeline" aria-label="{{ translate('Booking_Status') }}">
    <div class="booking-pipeline__track">
        @foreach ($pipelineSteps as $i => $step)
            @if($i > 0)
                <span class="booking-pipeline__line {{ $i <= $currentIndex && ! $isTerminal ? 'is-done' : '' }}"></span>
            @endif
            <div class="booking-pipeline__step {{ $i < $currentIndex && ! $isTerminal ? 'is-done' : '' }} {{ $i === $currentIndex && ! $isTerminal ? 'is-current' : '' }}">
                <span class="booking-pipeline__dot"></span>
                {{ $step['label'] }}
            </div>
        @endforeach
        @if($isTerminal)
            <span class="booking-pipeline__line"></span>
            <div class="booking-pipeline__step is-current">
                <span class="booking-pipeline__dot"></span>
                {{ booking_admin_booking_status_display_label($booking) }}
            </div>
        @endif
    </div>
</div>
