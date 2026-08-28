<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
        <tr>
            <th>{{ translate('Visit_date_and_time') }}</th>
            <th>{{ translate('Booking Id') }}</th>
            <th>{{ translate('Status') }}</th>
            <th>{{ translate('Visit_remarks') }}</th>
            <th class="text-end">{{ translate('Action') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($visits as $visit)
            @php
                $visitId = (string) ($visit['id'] ?? '');
                $visitStatus = $visit['booking_status'] ?? '';
                $visitBadge = 'info';
                if ($visitStatus === 'ongoing' || $visitStatus === 'on_hold') {
                    $visitBadge = 'warning';
                } elseif ($visitStatus === 'completed') {
                    $visitBadge = 'success';
                } elseif ($visitStatus === 'canceled' || $visitStatus === 'cancelled') {
                    $visitBadge = 'danger';
                }
                $canRescheduleThis = !empty($allowReschedule) && in_array($visitStatus, ['pending', 'accepted', 'ongoing', 'on_hold'], true);
                $visitWhen = !empty($visit['service_schedule']) ? date('d-M-Y h:ia', strtotime($visit['service_schedule'])) : '—';
                $historyCount = isset($visit['schedule_histories']) ? count($visit['schedule_histories']) : 0;
                $wasRescheduled = $historyCount != 0 && $historyCount != 1;
                $visitModalId = 'repeat-visit-modal-' . $visitId;
                $visitInvoiceUrl = (string) ($visit['invoice_url'] ?? '');
                $hasVisitInvoice = $visitInvoiceUrl !== '';
            @endphp
            <tr class="repeat-visit-row" id="repeat-visit-row-{{ $visitId }}">
                <td>
                    <div class="fw-medium">{{ $visitWhen }}</div>
                    @if ($wasRescheduled)
                        <span class="fz-12 text-muted">({{ translate('Rescheduled') }})</span>
                    @endif
                </td>
                <td>
                    <button type="button" class="btn btn-link p-0 fw-semibold js-repeat-visit-toggle" data-visit-id="{{ $visitId }}"
                            data-bs-toggle="modal" data-bs-target="#{{ $visitModalId }}">
                        #{{ $visit['readable_id'] ?? '' }}
                    </button>
                </td>
                <td>
                    <div class="d-flex flex-column align-items-start gap-2">
                        <span class="badge badge-{{ $visitBadge }}">{{ ucwords(str_replace('_', ' ', $visitStatus)) }}</span>
                        @include('bookingmodule::admin.booking.partials._repeat-visit-status-actions', ['visit' => $visit, 'compact' => true])
                    </div>
                </td>
                <td style="max-width: 280px;">
                    @include('bookingmodule::admin.booking.partials._repeat-visit-remarks', ['remarks' => $visit['visit_remarks'] ?? ''])
                </td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1 justify-content-end">
                        @if ($canRescheduleThis)
                            @can('booking_can_manage_status')
                                <button type="button" class="action-btn btn--light-primary" data-bs-toggle="modal"
                                        data-bs-target="#reschedule-{{ $visitId }}" style="--size: 30px"
                                        title="{{ translate('Reschedule_visit') }}">
                                    <span class="material-icons">pending_actions</span>
                                </button>
                            @endcan
                        @endif
                        @if($hasVisitInvoice)
                            <a href="{{ $visitInvoiceUrl }}" target="_blank"
                               class="action-btn btn--light-primary text-primary" style="--size: 30px"
                               title="{{ translate('Invoice') }}">
                                <span class="material-icons">description</span>
                            </a>
                        @endif
                        <button type="button" class="action-btn btn--light-primary js-repeat-visit-toggle" style="--size: 30px"
                                data-visit-id="{{ $visitId }}" data-bs-toggle="modal" data-bs-target="#{{ $visitModalId }}"
                                title="{{ translate('view_details') }}">
                            <span class="material-icons">visibility</span>
                        </button>
                    </div>
                </td>
            </tr>
            @if ($canRescheduleThis)
                @include('bookingmodule::admin.booking.partials._reschedule-repeat-visit-modal', [
                    'repeatId' => $visitId,
                    'schedule' => $visit['service_schedule'] ?? '',
                ])
            @endif
        @endforeach
        </tbody>
    </table>
</div>
@foreach ($visits as $visit)
    @include('bookingmodule::admin.booking.partials._repeat-visit-details-modal', ['visit' => $visit])
@endforeach
