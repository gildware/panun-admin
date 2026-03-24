@extends('adminmodule::layouts.master')

@section('title', translate('Performance'))

@push('css_or_js')
    <style>
        .perf-metric {
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: .75rem;
            padding: .85rem;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .02);
            height: 100%;
        }
        .perf-metric__label {
            font-size: .8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: .35rem;
        }
        .perf-metric__value {
            font-size: 1.1rem;
            font-weight: 900;
            color: #111827;
            line-height: 1.15;
        }
        .perf-status-btn {
            border: 2px solid currentColor !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        .perf-status-btn:hover,
        .perf-status-btn:focus,
        .perf-status-btn:active {
            color: #fff !important;
            border-color: transparent !important;
        }
        .perf-status-btn.btn-outline-success:hover,
        .perf-status-btn.btn-outline-success:focus,
        .perf-status-btn.btn-outline-success:active {
            background-color: #198754 !important;
        }
        .perf-status-btn.btn-outline-warning:hover,
        .perf-status-btn.btn-outline-warning:focus,
        .perf-status-btn.btn-outline-warning:active {
            background-color: #ffc107 !important;
        }
        .perf-status-btn.btn-outline-danger:hover,
        .perf-status-btn.btn-outline-danger:focus,
        .perf-status-btn.btn-outline-danger:active {
            background-color: #dc3545 !important;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @php
                    $customerDisplayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                    $customerDisplayName = $customerDisplayName !== '' ? $customerDisplayName : ($customer->email ?? translate('Customer'));
                    $customerStatus = (string) ($customer->manual_performance_status ?? 'active');
                    $customerStatusLabel = match($customerStatus) {
                        'blacklisted' => translate('Blacklisted'),
                        'suspended' => translate('Suspended'),
                        default => translate('Active'),
                    };
                    $customerStatusClass = match($customerStatus) {
                        'blacklisted' => 'bg-danger',
                        'suspended' => 'bg-warning text-dark',
                        default => 'bg-success',
                    };
                @endphp
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h2 class="page-title mb-2">{{ $customerDisplayName }}</h2>
                        <div>{{ translate('Joined_on') }} {{ date('d-M-y H:iA', strtotime($customer?->created_at)) }}</div>
                    </div>
                    <span class="badge {{ $customerStatusClass }}">{{ $customerStatusLabel }}</span>
                </div>
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'performance'])

            <div class="card">
                <div class="card-body p-30">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <h2 class="mb-0">{{ translate('Internal_Performance') }}</h2>
                        <div class="text-muted fs-12">{{ translate('From_admin_feedback_incidents') }}</div>
                    </div>

                    @php
                        $bookingsCompleted = (int) ($metrics->bookings_completed_count ?? 0);
                        $bookingsCancelled = (int) ($metrics->bookings_cancelled_count ?? 0);
                        $complaintsCount = (int) ($metrics->complaints_count ?? 0);
                        $positiveFeedbackCount = (int) ($metrics->positive_feedback_count ?? 0);
                        $performanceScore = (int) ($metrics->performance_score ?? 0);
                        $suggestedAction = (string) ($metrics->suggested_action ?? 'good_customer');
                        $manualStatus = (string) ($customer->manual_performance_status ?? 'active');
                        $manualStatusLabel = match($manualStatus) {
                            'suspended' => translate('Suspended'),
                            'blacklisted' => translate('Blacklisted'),
                            default => translate('Active'),
                        };
                    @endphp

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted fs-14">{{ translate('Manual_Status') }}:</span>
                            <span class="badge bg-dark">{{ $manualStatusLabel }}</span>
                            @if($manualStatus === 'suspended' && !empty($customer->performance_suspended_until))
                                <span class="text-muted fs-12">
                                    {{ translate('Until') }} {{ \Illuminate\Support\Carbon::parse($customer->performance_suspended_until)->format('Y-m-d H:i') }}
                                </span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if($manualStatus !== 'suspended')
                            <form method="POST" action="{{ route('admin.provider.customer-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <input type="hidden" name="manual_status" value="suspended">
                                <button type="button" class="btn btn-outline-warning btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Suspend_30_Days') }}">{{ translate('Suspend_30_Days') }}</button>
                            </form>
                            @endif
                            @if($manualStatus !== 'blacklisted')
                            <form method="POST" action="{{ route('admin.provider.customer-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <input type="hidden" name="manual_status" value="blacklisted">
                                <button type="button" class="btn btn-outline-danger btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Blacklist') }}">{{ translate('Blacklist') }}</button>
                            </form>
                            @endif
                            @if($manualStatus !== 'active')
                            <form method="POST" action="{{ route('admin.provider.customer-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <input type="hidden" name="manual_status" value="active">
                                <button type="button" class="btn btn-outline-success btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Set_Active') }}">{{ translate('Set_Active') }}</button>
                            </form>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Total_Bookings_Completed') }}</div>
                                <div class="perf-metric__value">{{ $bookingsCompleted }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Total_Bookings_Cancelled') }}</div>
                                <div class="perf-metric__value">{{ $bookingsCancelled }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Total_Complaints') }}</div>
                                <div class="perf-metric__value">{{ $complaintsCount }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Positive_Feedback_Count') }}</div>
                                <div class="perf-metric__value">{{ $positiveFeedbackCount }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Suggested_Action') }}</div>
                                <div class="perf-metric__value">{{ ucwords(str_replace('_', ' ', $suggestedAction)) }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Current_Performance_Score') }}</div>
                                <div class="perf-metric__value">{{ $performanceScore }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h3 class="mb-0">{{ translate('Incident_Timeline') }}</h3>
                            <div class="text-muted fs-12">{{ translate('Most_recent_first') }}</div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                <tr>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                    <th>{{ translate('Type') }}</th>
                                    <th>{{ translate('Tags') }}</th>
                                    <th>{{ translate('Booking_Id') }}</th>
                                    <th>{{ translate('Notes') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($incidents as $incident)
                                    @php
                                        $actionLabel = match($incident->action_type) {
                                            'provider_changed' => translate('Provider Changed'),
                                            'cancelled', 'canceled' => translate('Cancelled'),
                                            default => translate('Completed'),
                                        };
                                        $typeLabel = match($incident->incident_type) {
                                            'COMPLAINT' => translate('Complaint'),
                                            'POSITIVE_FEEDBACK' => translate('Positive Feedback'),
                                            'NON_COMPLAINT' => translate('Non-Complaint'),
                                            default => $incident->incident_type,
                                        };
                                        $tagsLabel = collect((array)($incident->tags ?? []))
                                            ->map(fn ($tag) => str_replace('_', ' ', ucfirst($tag)))
                                            ->implode(', ');
                                        $notes = $incident->notes ?: '-';
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $incident->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="fw-bold">{{ $actionLabel }}</td>
                                        <td>{{ $typeLabel }}</td>
                                        <td style="max-width:420px;">{{ $tagsLabel ?: '-' }}</td>
                                        <td>
                                            @if($incident->booking)
                                                <a href="{{ route('admin.booking.details', [$incident->booking_id]) }}"
                                                   class="text-decoration-none fw-medium text-primary">
                                                    #{{ $incident->booking->readable_id }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ translate('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td style="max-width:420px;">{{ $notes }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            {{ translate('No_incidents_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end">
                            {{ $incidents->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerStatusConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="customerStatusConfirmText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn--primary" id="customerStatusConfirmSubmit">{{ translate('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        let pendingCustomerStatusForm = null;
        const customerStatusModalEl = document.getElementById('customerStatusConfirmModal');
        const customerStatusModal = bootstrap.Modal.getOrCreateInstance(customerStatusModalEl);

        document.querySelectorAll('.confirm-status-change-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pendingCustomerStatusForm = btn.closest('form');
                const statusLabel = btn.dataset.statusLabel || '';
                document.getElementById('customerStatusConfirmText').textContent = `Are you sure you want to set status to "${statusLabel}"?`;
                customerStatusModal.show();
            });
        });

        document.getElementById('customerStatusConfirmSubmit').addEventListener('click', function () {
            if (pendingCustomerStatusForm) {
                pendingCustomerStatusForm.submit();
            }
        });
    </script>
@endpush
