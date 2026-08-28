@php
    $scheduledVisits = $booking['visitScheduled'] ?? [];
    $ongoingVisits = $booking['visitOngoing'] ?? [];
    $doneVisits = $booking['visitDone'] ?? [];
    $canceledVisits = $booking['visitCanceled'] ?? [];
    $scheduledCount = count($scheduledVisits);
    $ongoingCount = count($ongoingVisits);
    $doneCount = count($doneVisits);
    $canceledCount = count($canceledVisits);
    $activeVisitTab = 'scheduled';
    if ($ongoingCount != 0) {
        $activeVisitTab = 'ongoing';
    } elseif ($scheduledCount != 0) {
        $activeVisitTab = 'scheduled';
    } elseif ($doneCount != 0) {
        $activeVisitTab = 'done';
    } elseif ($canceledCount != 0) {
        $activeVisitTab = 'canceled';
    }
    $scheduledTabActive = $activeVisitTab === 'scheduled';
    $ongoingTabActive = $activeVisitTab === 'ongoing';
    $doneTabActive = $activeVisitTab === 'done';
    $canceledTabActive = $activeVisitTab === 'canceled';
@endphp

<div class="repeat-visit-board">
    <div class="repeat-visit-board__head">
    <ul class="nav nav-tabs repeat-visit-tabs mb-0" id="repeat-visit-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link {{ $scheduledTabActive ? 'active' : '' }}" id="repeat-visit-tab-btn-scheduled"
                    data-bs-toggle="tab" data-bs-target="#repeat-visit-tab-scheduled" role="tab"
                    aria-controls="repeat-visit-tab-scheduled" aria-selected="{{ $scheduledTabActive ? 'true' : 'false' }}">
                {{ translate('Scheduled') }} ({{ $scheduledCount }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link {{ $ongoingTabActive ? 'active' : '' }}" id="repeat-visit-tab-btn-ongoing"
                    data-bs-toggle="tab" data-bs-target="#repeat-visit-tab-ongoing" role="tab"
                    aria-controls="repeat-visit-tab-ongoing" aria-selected="{{ $ongoingTabActive ? 'true' : 'false' }}">
                {{ translate('Visits_in_progress') }} ({{ $ongoingCount }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link {{ $doneTabActive ? 'active' : '' }}" id="repeat-visit-tab-btn-done"
                    data-bs-toggle="tab" data-bs-target="#repeat-visit-tab-done" role="tab"
                    aria-controls="repeat-visit-tab-done" aria-selected="{{ $doneTabActive ? 'true' : 'false' }}">
                {{ translate('Visits_done') }} ({{ $doneCount }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link {{ $canceledTabActive ? 'active' : '' }}" id="repeat-visit-tab-btn-canceled"
                    data-bs-toggle="tab" data-bs-target="#repeat-visit-tab-canceled" role="tab"
                    aria-controls="repeat-visit-tab-canceled" aria-selected="{{ $canceledTabActive ? 'true' : 'false' }}">
                {{ translate('Canceled') }} ({{ $canceledCount }})
            </button>
        </li>
    </ul>
    </div>

    <div class="tab-content repeat-visit-tab-content" id="repeat-visit-tab-content">
        <div class="tab-pane fade {{ $scheduledTabActive ? 'show active' : '' }}" id="repeat-visit-tab-scheduled" role="tabpanel" aria-labelledby="repeat-visit-tab-btn-scheduled" tabindex="0">
            @if ($scheduledCount == 0)
                <div class="p-4">
                    <p class="mb-1 fw-semibold">{{ translate('No_scheduled_visits') }}</p>
                    <p class="text-muted mb-0">{{ translate('Schedule_visits_tab_help') }}</p>
                </div>
            @else
                @include('bookingmodule::admin.booking.partials._repeat-visits-table', ['visits' => $scheduledVisits, 'allowReschedule' => true])
            @endif
        </div>

        <div class="tab-pane fade {{ $ongoingTabActive ? 'show active' : '' }}" id="repeat-visit-tab-ongoing" role="tabpanel" aria-labelledby="repeat-visit-tab-btn-ongoing" tabindex="0">
            @if ($ongoingCount == 0)
                <div class="p-4">
                    <p class="text-muted mb-0">{{ translate('No_visits_in_progress') }}</p>
                </div>
            @else
                @include('bookingmodule::admin.booking.partials._repeat-visits-table', ['visits' => $ongoingVisits, 'allowReschedule' => true])
            @endif
        </div>

        <div class="tab-pane fade {{ $doneTabActive ? 'show active' : '' }}" id="repeat-visit-tab-done" role="tabpanel" aria-labelledby="repeat-visit-tab-btn-done" tabindex="0">
            @if ($doneCount == 0)
                <div class="p-4">
                    <p class="text-muted mb-0">{{ translate('No_visits_done') }}</p>
                </div>
            @else
                @include('bookingmodule::admin.booking.partials._repeat-visits-table', ['visits' => $doneVisits, 'allowReschedule' => false])
            @endif
        </div>

        <div class="tab-pane fade {{ $canceledTabActive ? 'show active' : '' }}" id="repeat-visit-tab-canceled" role="tabpanel" aria-labelledby="repeat-visit-tab-btn-canceled" tabindex="0">
            @if ($canceledCount == 0)
                <div class="p-4">
                    <p class="text-muted mb-0">{{ translate('No_canceled_visits') }}</p>
                </div>
            @else
                @include('bookingmodule::admin.booking.partials._repeat-visits-table', ['visits' => $canceledVisits, 'allowReschedule' => false])
            @endif
        </div>
    </div>
</div>
