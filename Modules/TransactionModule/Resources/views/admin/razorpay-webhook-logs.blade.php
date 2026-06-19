@extends('adminmodule::layouts.master')

@section('title', translate('Razorpay_webhook_logs'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Razorpay_webhook_logs') }}</h2>
                            <p class="text-muted small mb-0">{{ translate('Razorpay_webhook_logs_hint') }}</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 bg-primary bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Total') }}</p>
                                    <h4 class="mb-0">{{ $summary['total'] ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-success bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Successful') }}</p>
                                    <h4 class="mb-0 text-success">{{ $summary['successful'] ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-danger bg-opacity-10">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Failed') }}</p>
                                    <h4 class="mb-0 text-danger">{{ $summary['failed'] ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <p class="text-muted small mb-1">{{ translate('Last_received') }}</p>
                                    <h6 class="mb-0">
                                        {{ $summary['last_received_at'] ? $summary['last_received_at']->format('d M Y, h:i A') : translate('Never') }}
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <input type="text" name="search" value="{{ $search }}"
                                           class="form-control"
                                           placeholder="{{ translate('Search_by_payment_order_or_booking_id') }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="result" class="form-select">
                                        <option value="">{{ translate('All_results') }}</option>
                                        @foreach (['completed', 'already_completed', 'fulfillment_failed', 'not_found', 'amount_mismatch', 'invalid_signature', 'ignored', 'failed'] as $option)
                                            <option value="{{ $option }}" @selected($result === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn--primary w-100">{{ translate('Filter') }}</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>{{ translate('Time') }}</th>
                                        <th>{{ translate('Event') }}</th>
                                        <th>{{ translate('Razorpay_payment_id') }}</th>
                                        <th>{{ translate('Result') }}</th>
                                        <th>{{ translate('Booking_ID') }}</th>
                                        <th>{{ translate('HTTP') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse ($logs as $log)
                                        <tr>
                                            <td>{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                            <td>{{ $log->event ?: '-' }}</td>
                                            <td>{{ $log->razorpay_payment_id ?: '-' }}</td>
                                            <td>
                                                @php
                                                    $badgeClass = in_array($log->result, ['completed', 'already_completed'], true)
                                                        ? 'success'
                                                        : (in_array($log->result, ['ignored'], true) ? 'secondary' : 'danger');
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }}">{{ $log->result }}</span>
                                                @if ($log->error_message)
                                                    <div class="small text-danger">{{ \Illuminate\Support\Str::limit($log->error_message, 80) }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $log->booking_readable_id ?: '-' }}</td>
                                            <td>{{ $log->http_status }}</td>
                                            <td>
                                                <a href="{{ route('admin.transaction.razorpay_webhooks.show', $log->id) }}"
                                                   class="btn btn-sm btn-outline-primary">{{ translate('View') }}</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                {{ translate('No_webhook_logs_yet') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {!! $logs->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
