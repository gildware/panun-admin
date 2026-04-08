@extends('adminmodule::layouts.master')

@section('title', translate('provider_performance'))

@push('css_or_js')
    <style>
        .perf-pill {
            border-radius: 999px;
            padding: .35rem .75rem;
            font-weight: 800;
            font-size: .875rem;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .perf-pill--active {
            background: rgba(54, 179, 126, .15);
            border-color: rgba(54, 179, 126, .35);
            color: #138a57;
        }

        .perf-pill--warning {
            background: rgba(243, 156, 18, .15);
            border-color: rgba(243, 156, 18, .35);
            color: #b26b00;
        }

        .perf-pill--suspended, .perf-pill--blacklisted {
            background: rgba(231, 76, 60, .12);
            border-color: rgba(231, 76, 60, .35);
            color: #c0392b;
        }

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
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'special_bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=special_bookings">{{ translate('Special_Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="card-body p-30">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <h2 class="mb-0">{{ translate('Performance') }}</h2>

                        @php
                            $perfStatus = $provider?->manual_performance_status ?? 'active';
                            $pillClass = match($perfStatus) {
                                'suspended' => 'perf-pill--suspended',
                                'blacklisted' => 'perf-pill--blacklisted',
                                default => 'perf-pill--active',
                            };
                            $perfStatusLabel = match($perfStatus) {
                                'suspended' => translate('Suspended'),
                                'blacklisted' => translate('Blacklisted'),
                                default => translate('Active'),
                            };
                        @endphp
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <span class="perf-pill {{ $pillClass }}">{{ $perfStatusLabel }}</span>
                            @if($perfStatus === 'suspended' && !empty($provider->performance_suspended_until))
                                <span class="text-muted fs-12">
                                    {{ translate('Until') }} {{ \Illuminate\Support\Carbon::parse($provider->performance_suspended_until)->format('Y-m-d H:i') }}
                                </span>
                            @endif
                            @if($perfStatus !== 'suspended')
                            <form method="POST" action="{{ route('admin.provider.provider-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                <input type="hidden" name="manual_status" value="suspended">
                                <button type="button" class="btn btn-outline-warning btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Suspend_30_Days') }}">{{ translate('Suspend_30_Days') }}</button>
                            </form>
                            @endif
                            @if($perfStatus !== 'blacklisted')
                            <form method="POST" action="{{ route('admin.provider.provider-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                <input type="hidden" name="manual_status" value="blacklisted">
                                <button type="button" class="btn btn-outline-danger btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Blacklist') }}">{{ translate('Blacklist') }}</button>
                            </form>
                            @endif
                            @if($perfStatus !== 'active')
                            <form method="POST" action="{{ route('admin.provider.provider-performance-status.update') }}" class="manual-status-form">
                                @csrf
                                <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                                <input type="hidden" name="manual_status" value="active">
                                <button type="button" class="btn btn-outline-success btn-sm perf-status-btn confirm-status-change-btn" data-status-label="{{ translate('Set_Active') }}">{{ translate('Set_Active') }}</button>
                            </form>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        @php
                            $bookingsCompleted = (int) ($metrics->bookings_completed_count ?? $metrics->jobs_completed_count ?? 0);
                            $bookingsCancelled = (int) ($metrics->bookings_cancelled_count ?? 0);
                            $complaintsCount = (int) ($metrics->complaints_count ?? 0);
                            $noShowCount = (int) ($metrics->no_show_count ?? 0);
                            $lateArrivalCount = (int) ($metrics->late_arrival_count ?? 0);
                            $poorServiceCount = (int) ($metrics->poor_service_count ?? 0);
                            $positiveFeedbackCount = (int) ($metrics->positive_feedback_count ?? 0);
                            $reopenedBookingsCount = (int) ($metrics->reopened_bookings_count ?? 0);
                            $performanceScore = (int) ($metrics->performance_score ?? 0);
                            $suggestedAction = (string) ($metrics->suggested_action ?? 'keep_active');
                        @endphp

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
                                <div class="perf-metric__label">{{ translate('No_Show_Count') }}</div>
                                <div class="perf-metric__value">{{ $noShowCount }}</div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Late_Arrival_Count') }}</div>
                                <div class="perf-metric__value">{{ $lateArrivalCount }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Poor_Service_Count') }}</div>
                                <div class="perf-metric__value">{{ $poorServiceCount }}</div>
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
                                <div class="perf-metric__label">{{ translate('Reopened_Bookings_Count') }}</div>
                                <div class="perf-metric__value">{{ $reopenedBookingsCount }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Suggested_Action') }}</div>
                                <div class="perf-metric__value">
                                    {{ ucwords(str_replace('_', ' ', $suggestedAction)) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-md-12">
                            <div class="perf-metric">
                                <div class="perf-metric__label">{{ translate('Current_Performance_Score') }}</div>
                                <div class="d-flex align-items-end justify-content-between gap-3">
                                    <div class="perf-metric__value mb-0">{{ $performanceScore }}</div>
                                    <div class="text-muted fs-12 mb-0">
                                        {{ translate('Calculated_from_internal_incidents') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h3 class="mb-0">{{ translate('Incident_Timeline') }}</h3>
                            <div class="text-muted fs-12">
                                {{ translate('Most_recent_first') }}
                            </div>
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
                                            'reopened' => translate('Reopened'),
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
                                                <a href="{{ route('admin.booking.details', [$incident->booking_id, 'web_page' => 'details']) }}"
                                                   class="text-decoration-none fw-medium text-primary">
                                                    #{{ $incident->booking->readable_id }}
                                                </a>
                                            @else
                                                <span class="text-muted" title="{{ $incident->booking_id }}">{{ translate('N/A') }}</span>
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

    <div class="modal fade" id="providerStatusConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="providerStatusConfirmText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn--primary" id="providerStatusConfirmSubmit">{{ translate('Confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        let pendingProviderStatusForm = null;
        const providerStatusModalEl = document.getElementById('providerStatusConfirmModal');
        const providerStatusModal = bootstrap.Modal.getOrCreateInstance(providerStatusModalEl);

        document.querySelectorAll('.confirm-status-change-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pendingProviderStatusForm = btn.closest('form');
                const statusLabel = btn.dataset.statusLabel || '';
                document.getElementById('providerStatusConfirmText').textContent = `Are you sure you want to set status to "${statusLabel}"?`;
                providerStatusModal.show();
            });
        });

        document.getElementById('providerStatusConfirmSubmit').addEventListener('click', function () {
            if (pendingProviderStatusForm) {
                pendingProviderStatusForm.submit();
            }
        });
    </script>
@endpush

