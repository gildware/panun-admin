@php
    $bookingStatusBreakdown = $bookingStatusBreakdown ?? ($analytics['booking_status_breakdown'] ?? []);
@endphp

@if($bookingStatusBreakdown !== [])
    <div class="booking-status-section">
        @include('adminmodule::partials._employee-progress-section-label', [
            'label' => translate('Booking_report_summary'),
            'helpKey' => 'chart_mix',
        ])
        @include('adminmodule::partials._employee-progress-lead-metric-grid', [
            'rows' => collect($bookingStatusBreakdown)->map(function ($row) {
                $row['help_key'] = 'booking_status_'.($row['key'] ?? '');

                return $row;
            })->all(),
            'gridClass' => 'lead-metric-grid lead-metric-grid--bookings',
        ])
    </div>
@endif
