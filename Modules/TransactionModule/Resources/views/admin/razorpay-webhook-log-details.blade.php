@extends('adminmodule::layouts.master')

@section('title', translate('Razorpay_webhook_log_details'))

@section('content')
    @php
        $isSuccess = in_array($log->result, ['completed', 'already_completed'], true);
        $alertClass = $isSuccess ? 'success' : (in_array($log->result, ['ignored'], true) ? 'secondary' : 'danger');
    @endphp

    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-3">
                        <h2 class="page-title">{{ translate('Razorpay_webhook_log_details') }}</h2>
                        <a href="{{ route('admin.transaction.razorpay_webhooks.index') }}" class="btn btn-outline-primary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="alert alert-{{ $alertClass }} mb-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <strong>{{ translate('Result') }}:</strong>
                                <span class="badge bg-{{ $isSuccess ? 'success' : 'danger' }}">{{ $log->result ?: 'unknown' }}</span>
                                <span class="ms-2"><strong>{{ translate('HTTP') }}:</strong> {{ $log->http_status }}</span>
                            </div>
                            <div class="text-muted small">{{ $log->created_at?->format('d M Y, h:i A') }}</div>
                        </div>
                        <div class="mb-2">
                            <strong>{{ translate('What_happened') }}:</strong>
                            {{ $detail['diagnosis'] ?? '-' }}
                        </div>
                        @if (! empty($detail['error_message']))
                            <div class="mb-0">
                                <strong>{{ translate('Error') }}:</strong>
                                <span class="fw-semibold">{{ $detail['error_message'] }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ translate('Summary') }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <tbody>
                                    <tr>
                                        <th style="width: 220px;">{{ translate('Event') }}</th>
                                        <td>{{ $log->event ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Signature_valid') }}</th>
                                        <td>{{ $log->signature_valid ? translate('Yes') : translate('No') }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Razorpay_payment_id') }}</th>
                                        <td>{{ $log->razorpay_payment_id ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Razorpay_order_id') }}</th>
                                        <td>{{ $log->razorpay_order_id ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Payment_request_id') }}</th>
                                        <td>{{ $detail['resolved_payment_request_id'] ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Payment_request_in_database') }}</th>
                                        <td>
                                            @if ($detail['payment_request_exists'])
                                                {{ translate('Yes') }}
                                                @if ($detail['payment_request_is_paid'])
                                                    <span class="badge bg-success ms-1">{{ translate('Paid') }}</span>
                                                @else
                                                    <span class="badge bg-warning text-dark ms-1">{{ translate('Unpaid') }}</span>
                                                @endif
                                            @else
                                                <span class="text-danger">{{ translate('No') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Paid_amount') }}</th>
                                        <td>
                                            @if ($detail['paid_amount'] !== null)
                                                {{ with_currency_symbol($detail['paid_amount']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Expected_checkout_amount') }}</th>
                                        <td>
                                            @if ($detail['expected_amount'] !== null)
                                                {{ with_currency_symbol($detail['expected_amount']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Cart_items') }}</th>
                                        <td>
                                            @if ($detail['cart_items'] === null)
                                                -
                                            @elseif ($detail['cart_items'] === 0)
                                                <span class="text-danger">0 ({{ translate('Empty_cart') }})</span>
                                            @else
                                                {{ $detail['cart_items'] }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Booking_ID') }}</th>
                                        <td>{{ $log->booking_readable_id ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ translate('Booking_exists') }}</th>
                                        <td>
                                            @if ($detail['booking_exists'] === null)
                                                -
                                            @elseif ($detail['booking_exists'])
                                                {{ translate('Yes') }}
                                            @else
                                                <span class="text-danger">{{ translate('No') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">{{ translate('Payload') }}</h5>
                        </div>
                        <div class="card-body">
                            <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
