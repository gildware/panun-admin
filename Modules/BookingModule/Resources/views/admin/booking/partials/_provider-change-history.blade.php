@php
    $withdrawalHistories = ($booking->providerWithdrawalStatusHistories ?? collect());
    if ($withdrawalHistories->isEmpty() && $booking->provider_cancelled_at && $booking->provider_cancelled_by_provider_id) {
        $fallback = $booking->latestParentProviderCancellationStatusHistory
            ?? $booking->latestProviderRejectionHistory
            ?? $booking->latestPendingCancellationRequestHistory;
        if ($fallback) {
            $withdrawalHistories = collect([$fallback]);
        }
    }
@endphp
@if($withdrawalHistories->isNotEmpty() || $booking->provider_cancelled_by_provider_id)
    <div class="card mb-3">
        <div class="card-body py-3 px-3">
            <h6 class="c1 mb-3 d-flex align-items-center gap-1 fz-12 text-uppercase">
                <span class="material-icons title-color fz-16">swap_horiz</span>
                {{ translate('Provider_change_history') }}
            </h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th>{{ translate('Date') }}</th>
                        <th>{{ translate('Provider') }}</th>
                        <th>{{ translate('Action') }}</th>
                        <th>{{ translate('Reason') }}</th>
                        <th>{{ translate('Status_change_remarks') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($withdrawalHistories as $history)
                        <tr>
                            <td class="text-nowrap">{{ optional($history->created_at)->format('d-M-Y h:i A') ?? '—' }}</td>
                            <td>
                                @if($booking->providerCancelledByProvider)
                                    <a href="{{ route('admin.provider.details', [$booking->provider_cancelled_by_provider_id, 'web_page' => 'withdrawn_bookings']) }}">
                                        {{ $booking->providerCancelledByProvider->company_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-warning">{{ translate('Provider_withdrew_or_rejected') }}</span>
                            </td>
                            <td>{{ $history->providerCancellationReason->name ?? '—' }}</td>
                            <td>{{ $history->status_change_remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                    @if($booking->originated_from_booking_id && $booking->originatedFromBooking)
                        <tr>
                            <td class="text-nowrap">{{ optional($booking->created_at)->format('d-M-Y h:i A') }}</td>
                            <td>{{ $booking->provider->company_name ?? '—' }}</td>
                            <td><span class="badge badge-info">{{ translate('Provider_cancellation_replacement') }}</span></td>
                            <td colspan="2">
                                {{ translate('Cloned_from_booking_after_provider_cancellation') }}:
                                <a href="{{ route('admin.booking.details', [$booking->originated_from_booking_id, 'web_page' => 'details']) }}">
                                    #{{ $booking->originatedFromBooking->readable_id ?? $booking->originated_from_booking_id }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
