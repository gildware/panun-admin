@extends('adminmodule::layouts.master')

@section('title', translate('Razorpay_webhook_log_details'))

@section('content')
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

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4"><strong>{{ translate('Time') }}:</strong> {{ $log->created_at?->format('d M Y, h:i A') }}</div>
                                <div class="col-md-4"><strong>{{ translate('Event') }}:</strong> {{ $log->event ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('Result') }}:</strong> {{ $log->result ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('Signature_valid') }}:</strong> {{ $log->signature_valid ? translate('Yes') : translate('No') }}</div>
                                <div class="col-md-4"><strong>{{ translate('Razorpay_payment_id') }}:</strong> {{ $log->razorpay_payment_id ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('Razorpay_order_id') }}:</strong> {{ $log->razorpay_order_id ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('Payment_request_id') }}:</strong> {{ $log->payment_request_id ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('Booking_ID') }}:</strong> {{ $log->booking_readable_id ?: '-' }}</div>
                                <div class="col-md-4"><strong>{{ translate('HTTP') }}:</strong> {{ $log->http_status }}</div>
                                @if ($log->error_message)
                                    <div class="col-12">
                                        <strong>{{ translate('Error') }}:</strong>
                                        <div class="text-danger">{{ $log->error_message }}</div>
                                    </div>
                                @endif
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
