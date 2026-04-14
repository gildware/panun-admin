@extends('adminmodule::layouts.master')

@section('title', translate('Payment'))

@push('css_or_js')
    <style>
        .flow-card {
            border: 1px solid transparent;
        }
        .flow-card--company-in {
            border-color: rgba(13, 110, 253, .35);
            background: rgba(13, 110, 253, .06);
        }
        .flow-card--company-out {
            border-color: rgba(220, 53, 69, .35);
            background: rgba(220, 53, 69, .06);
        }
        .flow-card--provider-in {
            border-color: rgba(25, 135, 84, .35);
            background: rgba(25, 135, 84, .06);
        }
        tr.customer-payment-report-row--loss-making > td {
            background-color: rgba(220, 53, 69, .09);
            border-color: rgba(220, 53, 69, .25);
        }
        tr.customer-payment-report-row--loss-making > td:first-child {
            box-shadow: inset 3px 0 0 0 #dc3545;
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

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'payments'])

            <div class="card">
                <div class="card-body p-30">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <h2 class="mb-0">{{ translate('Payment') }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @can('customer_update')
                                @if((float) ($pendingCollectionLossMaking ?? 0) > 0.009)
                                    <button type="button" class="btn btn-outline--primary text-capitalize" data-bs-toggle="modal" data-bs-target="#customerPaymentReminderWaModal">
                                        {{ translate('Send_payment_reminder_to_customer') }}
                                    </button>
                                @endif
                            @endcan
                            <div class="text-muted fs-12">{{ translate('Booking_wise_customer_payment_breakdown') }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--company-in h-100">
                                <h3>{{ translate('Customer_paid_to_company') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->customer_paid_to_company ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--company-out h-100">
                                <h3>{{ translate('Company_paid_to_customer') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->company_paid_to_customer ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 flow-card flow-card--provider-in h-100">
                                <h3>{{ translate('Customer_paid_to_provider') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($totals->customer_paid_to_provider ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 h-100 border border-warning flow-card" style="background: rgba(255, 193, 7, .08); border-color: rgba(255, 193, 7, .45) !important;">
                                <h3 title="{{ translate('Customer_pending_bad_debt_loss_making_hint') }}">{{ translate('Customer_pending_bad_debt_loss_making') }}</h3>
                                <h2 class="{{ ($lossMakingBadDebtNotDueTotal ?? 0) > 0.009 ? 'text-warning' : '' }}">{{ with_currency_symbol((float) ($lossMakingBadDebtNotDueTotal ?? 0)) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 h-100 border border-danger flow-card" style="background: rgba(220, 53, 69, .06); border-color: rgba(220, 53, 69, .35) !important;">
                                <h3>{{ translate('Customer_compensation_from_company') }}</h3>
                                <h2 class="{{ ($customerCompFromCompanyTotal ?? 0) > 0.009 ? 'text-danger' : '' }}">{{ with_currency_symbol((float) ($customerCompFromCompanyTotal ?? 0)) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="statistics-card statistics-card__style2 h-100 border border-warning flow-card" style="background: rgba(255, 193, 7, .08); border-color: rgba(255, 193, 7, .45) !important;">
                                <h3>{{ translate('Customer_compensation_from_provider') }}</h3>
                                <h2>{{ with_currency_symbol((float) ($customerCompFromProviderTotal ?? 0)) }}</h2>
                            </div>
                        </div>
                    </div>

                    <h3 class="h5 mb-3">{{ translate('Customer_booking_wise_payment_report') }}</h3>
                    <div class="table-responsive mb-5">
                        <table class="table align-middle table-bordered">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Booking_Id') }}</th>
                                <th class="text-end">{{ translate('Total_Amount') }}</th>
                                <th class="text-end">{{ translate('Paid_to_provider') }}</th>
                                <th class="text-end">{{ translate('Paid_to_company') }}</th>
                                <th class="text-end">{{ translate('Balance') }}</th>
                                <th class="text-end">{{ translate('Customer_report_pending_debit_loss_making') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($paginatedBookingReport as $bRow)
                                <tr @class(['customer-payment-report-row--loss-making' => !empty($bRow->is_loss_making)])>
                                    <td>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <a href="{{ route('admin.booking.details', [$bRow->booking_id]) }}" class="fw-semibold text-decoration-none @if(!empty($bRow->is_loss_making)) text-danger @elseif(!empty($bRow->is_scaled_loss_recovered)) text-success @endif">
                                                #{{ $bRow->readable_id }}
                                            </a>
                                            @if(!empty($bRow->is_loss_making))
                                                <span class="badge bg-danger">{{ translate('Bfs_badge_loss_making_booking') }}</span>
                                            @elseif(!empty($bRow->is_scaled_loss_recovered))
                                                <span class="badge bg-success">{{ translate('Bfs_badge_loss_recovered_booking') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end @if(!empty($bRow->is_loss_making)) text-danger fw-semibold @elseif(!empty($bRow->is_scaled_loss_recovered)) text-success fw-semibold @endif">{{ with_currency_symbol((float) $bRow->total_amount) }}</td>
                                    <td class="text-end">{{ with_currency_symbol((float) $bRow->paid_to_provider) }}</td>
                                    <td class="text-end">{{ with_currency_symbol((float) $bRow->paid_to_company) }}</td>
                                    <td class="text-end fw-semibold @if((float) $bRow->balance > 0.009) text-warning @elseif(!empty($bRow->is_loss_making)) text-danger @endif">{{ with_currency_symbol((float) $bRow->balance) }}</td>
                                    <td class="text-end fw-semibold">
                                        @if(!empty($bRow->is_loss_making))
                                            @if((float) ($bRow->pending_debit_loss_making ?? 0) > 0.009)
                                                <span class="text-danger">{{ with_currency_symbol((float) $bRow->pending_debit_loss_making) }}</span>
                                            @else
                                                <span class="text-muted">{{ with_currency_symbol(0) }}</span>
                                            @endif
                                        @elseif(!empty($bRow->is_scaled_loss_recovered))
                                            <span class="text-muted">{{ with_currency_symbol(0) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($paginatedBookingReport->hasPages())
                        <div class="d-flex justify-content-end mb-5">
                            {{ $paginatedBookingReport->links() }}
                        </div>
                    @endif

                    <h3 class="h5 mb-2">{{ translate('Payment_ledger_company_counterparty') }}</h3>
                    <p class="text-muted small mb-3">{{ translate('Payment_ledger_customer_tab_hint') }}</p>
                    <div class="table-responsive mb-5">
                        <table class="table align-middle table-bordered">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Date') }}</th>
                                <th>{{ translate('Booking_Id') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Payment_ledger_column_payment_type') }}</th>
                                <th>{{ translate('payment_method') }}</th>
                                <th>{{ translate('Transaction_ID') }}</th>
                                <th class="text-end">{{ translate('Amount') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($paginatedLedger as $entry)
                                <tr>
                                    <td class="text-nowrap">{{ $entry->created_at ? $entry->created_at->format('Y-m-d H:i') : '—' }}</td>
                                    <td>
                                        @if($entry->booking_id)
                                            <a href="{{ route('admin.booking.details', [$entry->booking_id]) }}" class="fw-semibold text-decoration-none">
                                                #{{ $entry->booking?->readable_id ?? $entry->booking_id }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($entry->type === \Modules\TransactionModule\Entities\LedgerTransaction::TYPE_IN)
                                            <span class="badge bg-success">{{ translate('In') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ translate('Out') }}</span>
                                        @endif
                                    </td>
                                    <td>{!! payment_counterparty_flow_badge_html($entry->counterpartyFlowKey()) !!}</td>
                                    <td>{{ $entry->formatPaymentMethodForDisplay() }}</td>
                                    <td>{{ $entry->transaction_id ?: '—' }}</td>
                                    <td class="text-end">{{ with_currency_symbol((float) $entry->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ translate('No_ledger_entries') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($paginatedLedger->hasPages())
                        <div class="d-flex justify-content-end mb-5">
                            {{ $paginatedLedger->links() }}
                        </div>
                    @endif

                    <h4 class="mb-2">{{ translate('Payment_transactions_all_parties') }}</h4>
                    <p class="text-muted small mb-3">{{ translate('Payment_transactions_booking_log_hint') }}</p>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('Date') }}</th>
                                <th>{{ translate('Booking_ID') }}</th>
                                <th>{{ translate('Payment_transactions_column_company_flow') }}</th>
                                <th>{{ translate('Payment_ledger_column_payment_type') }}</th>
                                <th>{{ translate('Channel') }}</th>
                                <th>{{ translate('Transaction_ID') }}</th>
                                <th class="text-end">{{ translate('Amount') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($paginatedTransactions as $row)
                                @php
                                    $cf = (string) ($row->company_flow ?? '');
                                    $cfLabel = match ($cf) {
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_IN => translate('Company_money_flow_in'),
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_OUT => translate('Company_money_flow_out'),
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_NONE => translate('Company_money_flow_none'),
                                        default => '—',
                                    };
                                    $cfBadge = match ($cf) {
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_IN => 'bg-success',
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_OUT => 'bg-danger',
                                        \Modules\TransactionModule\Entities\Transaction::FLOW_NONE => 'bg-secondary',
                                        default => 'bg-light text-dark',
                                    };
                                    $__flowKey = (string) ($row->counterparty_flow ?? 'unknown');
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $row->date ? \Illuminate\Support\Carbon::parse($row->date)->format('d M Y H:i') : '—' }}</td>
                                    <td>
                                        @if(!empty($row->booking_id))
                                            <a href="{{ route('admin.booking.details', [$row->booking_id]) }}" class="fw-semibold text-decoration-none" target="_blank" rel="noopener noreferrer">{{ $row->booking_readable_id ?? $row->booking_id }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $cfBadge }}">{{ $cfLabel }}</span></td>
                                    <td>{!! payment_counterparty_flow_badge_html($__flowKey) !!}</td>
                                    <td class="text-nowrap">{{ $row->channel }}</td>
                                    <td>{{ $row->transaction_id ?: '—' }}</td>
                                    <td class="text-end fw-semibold">{{ with_currency_symbol((float) $row->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ translate('No_data_available') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($paginatedTransactions->hasPages())
                        <div class="d-flex justify-content-end mb-30">
                            {{ $paginatedTransactions->links() }}
                        </div>
                    @endif

                    @can('customer_update')
                        @if((float) ($pendingCollectionLossMaking ?? 0) > 0.009)
                            <div class="modal fade" id="customerPaymentReminderWaModal" tabindex="-1" aria-labelledby="customerPaymentReminderWaModalLabel" aria-hidden="true"
                                 data-preview-url="{{ route('admin.customer.detail.whatsapp.customer_payment_reminder.preview', $customer->id) }}">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="customerPaymentReminderWaModalLabel">{{ translate('Send_payment_reminder_to_customer') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-2">{{ translate('WhatsApp_payment_reminder_modal_intro') }}</p>
                                            <p class="small mb-2"><span class="text-muted">{{ translate('phone') }}:</span> <span class="fw-semibold" id="pk-customer-reminder-phone">—</span></p>
                                            <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; min-height: 4rem;" id="pk-customer-reminder-preview">{{ translate('Loading…') }}</div>
                                            <p class="text-danger small mt-2 d-none" id="pk-customer-reminder-err"></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                            <form method="post" action="{{ route('admin.customer.detail.whatsapp.customer_payment_reminder.send', $customer->id) }}" class="d-inline" id="pk-customer-reminder-send-form">
                                                @csrf
                                                <button type="submit" class="btn btn--primary" id="pk-customer-reminder-submit">{{ translate('Send') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="waLedgerSendResultModal" tabindex="-1" aria-labelledby="waLedgerSendResultModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="waLedgerSendResultModalLabel" data-role="result-title">{{ translate('WhatsApp_message_sent_modal_title') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-0" id="waLedgerSendResultMessage"></p>
                                        </div>
                                        <div class="modal-footer flex-wrap gap-2">
                                            <a href="#" class="btn btn--primary d-none" id="waLedgerSendResultChatBtn">{{ translate('WhatsApp_view_chat_inbox') }}</a>
                                            <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('close') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @can('customer_update')
        @if((float) ($pendingCollectionLossMaking ?? 0) > 0.009)
            @push('script')
                <script>
                    (function () {
                        var modal = document.getElementById('customerPaymentReminderWaModal');
                        if (!modal || typeof fetch === 'undefined') return;
                        var previewEl = document.getElementById('pk-customer-reminder-preview');
                        var errEl = document.getElementById('pk-customer-reminder-err');
                        var phoneEl = document.getElementById('pk-customer-reminder-phone');
                        var submitBtn = document.getElementById('pk-customer-reminder-submit');
                        function csrf() {
                            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        }
                        modal.addEventListener('show.bs.modal', function () {
                            if (previewEl) previewEl.textContent = @json(translate('Loading…'));
                            if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); }
                            if (phoneEl) phoneEl.textContent = '—';
                            if (submitBtn) submitBtn.disabled = true;
                            var url = modal.getAttribute('data-preview-url');
                            if (!url || !previewEl) return;
                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrf(),
                                    'Accept': 'application/json'
                                },
                                body: '{}'
                            }).then(function (r) { return r.json(); }).then(function (data) {
                                if (phoneEl) phoneEl.textContent = data.phone || '—';
                                if (data.ok) {
                                    if (previewEl) previewEl.textContent = data.body || '';
                                    if (submitBtn) submitBtn.disabled = false;
                                } else {
                                    if (previewEl) previewEl.textContent = '';
                                    if (errEl) {
                                        errEl.textContent = data.message || data.error || '';
                                        errEl.classList.remove('d-none');
                                    }
                                }
                            }).catch(function () {
                                if (previewEl) previewEl.textContent = '';
                                if (errEl) {
                                    errEl.textContent = @json(translate('WhatsApp_payment_reminder_failed'));
                                    errEl.classList.remove('d-none');
                                }
                            });
                        });
                        var sendForm = document.getElementById('pk-customer-reminder-send-form');
                        var resultModalEl = document.getElementById('waLedgerSendResultModal');
                        if (sendForm && resultModalEl && typeof bootstrap !== 'undefined') {
                            function csrfSend() {
                                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            }
                            var titleOk = @json(translate('WhatsApp_message_sent_modal_title'));
                            var titleFail = @json(translate('WhatsApp_message_send_failed_modal_title'));
                            sendForm.addEventListener('submit', function (e) {
                                e.preventDefault();
                                var submitBtn = document.getElementById('pk-customer-reminder-submit');
                                if (submitBtn) submitBtn.disabled = true;
                                var fd = new FormData(sendForm);
                                fetch(sendForm.action, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': csrfSend(),
                                        'X-Requested-With': 'XMLHttpRequest'
                                    },
                                    body: fd,
                                    credentials: 'same-origin'
                                }).then(function (r) {
                                    return r.json().then(function (data) {
                                        return { status: r.status, data: data };
                                    });
                                }).then(function (pack) {
                                    var data = pack.data || {};
                                    var reminderEl = document.getElementById('customerPaymentReminderWaModal');
                                    if (reminderEl) {
                                        var inst = bootstrap.Modal.getInstance(reminderEl);
                                        if (inst) inst.hide();
                                    }
                                    var titleEl = resultModalEl.querySelector('[data-role="result-title"]');
                                    var msgEl = document.getElementById('waLedgerSendResultMessage');
                                    var chatBtn = document.getElementById('waLedgerSendResultChatBtn');
                                    if (titleEl) titleEl.textContent = data.ok ? titleOk : titleFail;
                                    if (msgEl) msgEl.textContent = data.message || '';
                                    if (chatBtn) {
                                        if (data.ok && data.show_chat_link && data.chat_url) {
                                            chatBtn.href = data.chat_url;
                                            chatBtn.classList.remove('d-none');
                                        } else {
                                            chatBtn.classList.add('d-none');
                                            chatBtn.removeAttribute('href');
                                        }
                                    }
                                    bootstrap.Modal.getOrCreateInstance(resultModalEl).show();
                                }).catch(function () {
                                    var reminderEl = document.getElementById('customerPaymentReminderWaModal');
                                    if (reminderEl) {
                                        var inst = bootstrap.Modal.getInstance(reminderEl);
                                        if (inst) inst.hide();
                                    }
                                    var titleEl = resultModalEl.querySelector('[data-role="result-title"]');
                                    var msgEl = document.getElementById('waLedgerSendResultMessage');
                                    var chatBtn = document.getElementById('waLedgerSendResultChatBtn');
                                    if (titleEl) titleEl.textContent = titleFail;
                                    if (msgEl) msgEl.textContent = @json(translate('WhatsApp_payment_reminder_failed'));
                                    if (chatBtn) chatBtn.classList.add('d-none');
                                    bootstrap.Modal.getOrCreateInstance(resultModalEl).show();
                                }).finally(function () {
                                    if (submitBtn) submitBtn.disabled = false;
                                });
                            });
                        }
                    })();
                </script>
            @endpush
        @endif
    @endcan
@endsection
