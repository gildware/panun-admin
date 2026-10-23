@extends('adminmodule::layouts.master')

@section('title', translate('Deleted_bookings'))

@push('css_or_js')
    @include('bookingmodule::admin.booking.partials._booking-status-colors-styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <h2 class="page-title mb-0">{{ translate('Deleted_bookings') }}</h2>
                <a href="{{ route('admin.booking.list', ['booking_status' => 'all', 'service_type' => 'all']) }}" class="btn btn--secondary">
                    {{ translate('Back_to_bookings') }}
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('admin.booking.deleted') }}" class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <input type="text" name="search" value="{{ $search }}" class="form-control"
                                   placeholder="{{ translate('Search_booking') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn--primary">{{ translate('Search') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th>{{ translate('Booking') }}</th>
                                <th>{{ translate('Customer') }}</th>
                                <th>{{ translate('Provider') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Amount') }}</th>
                                <th>{{ translate('Deleted_by') }}</th>
                                <th>{{ translate('Deleted_at') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($bookings as $booking)
                                @php
                                    $customer = $booking->customer;
                                    $customerName = trim((string) (($customer->first_name ?? '').' '.($customer->last_name ?? '')));
                                    $provider = $booking->provider;
                                    $providerName = trim((string) ($provider->company_name ?? ''));
                                    $deleter = $booking->deletedByUser;
                                    $deleterName = trim((string) (($deleter->first_name ?? '').' '.($deleter->last_name ?? '')));
                                    if ($deleterName === '' && $deleter) {
                                        $deleterName = (string) ($deleter->email ?: $deleter->phone);
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $booking->readable_id ?: $booking->id }}</td>
                                    <td>
                                        @if($customer)
                                            <a href="{{ route('admin.customer.detail', [$customer->id, 'web_page' => 'overview']) }}">
                                                {{ $customerName !== '' ? $customerName : '—' }}
                                                @if(!empty($customer->phone))
                                                    <div class="text-muted small">{{ $customer->phone }}</div>
                                                @endif
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($provider)
                                            <a href="{{ route('admin.provider.details', [$provider->id, 'web_page' => 'overview']) }}">
                                                {{ $providerName !== '' ? $providerName : '—' }}
                                                @if(!empty($provider->company_phone))
                                                    <div class="text-muted small">{{ $provider->company_phone }}</div>
                                                @endif
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>@include('bookingmodule::admin.booking.partials._booking-list-status-badge', ['booking' => $booking])</td>
                                    <td>{{ number_format((float) $booking->total_booking_amount, 2) }}</td>
                                    <td>{{ $deleterName !== '' ? $deleterName : '—' }}</td>
                                    <td>{{ optional($booking->deleted_at)->format('d M Y, h:i a') }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}" class="btn btn-sm btn--primary">{{ translate('view') }}</a>
                                        @can('booking_delete')
                                            <button type="button"
                                                    class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#restoreBookingModal"
                                                    data-action="{{ route('admin.booking.deleted.restore', $booking->id) }}"
                                                    data-name="{{ $booking->readable_id ?: $booking->id }}">
                                                {{ translate('Restore') }}
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#forceDeleteBookingModal"
                                                    data-action="{{ route('admin.booking.deleted.force', $booking->id) }}"
                                                    data-name="{{ $booking->readable_id ?: $booking->id }}">
                                                {{ translate('Delete_permanently') }}
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">{{ translate('No_deleted_bookings') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($bookings->hasPages())
                    <div class="card-footer">{{ $bookings->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="restoreBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="">
                    @csrf
                    <div class="modal-header border-0">
                        <h5 class="modal-title">{{ translate('Restore') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_restore_booking') }}</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ translate('Restore') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="forceDeleteBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-danger">{{ translate('Delete_permanently') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0" data-confirm-text>{{ translate('Confirm_permanent_delete_booking') }}</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ translate('Delete_permanently') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            ['restoreBookingModal', 'forceDeleteBookingModal'].forEach(function (id) {
                var modal = document.getElementById(id);
                if (!modal) return;
                modal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    if (!button) return;
                    modal.querySelector('form').action = button.getAttribute('data-action') || '';
                    var text = modal.querySelector('[data-confirm-text]');
                    if (!text) return;
                    if (!text.getAttribute('data-base')) {
                        text.setAttribute('data-base', text.textContent);
                    }
                    var name = button.getAttribute('data-name') || '';
                    text.textContent = text.getAttribute('data-base') + (name ? ' (' + name + ')' : '');
                });
            });
        });
    </script>
@endpush
