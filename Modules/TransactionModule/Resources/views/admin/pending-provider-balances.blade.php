@extends('adminmodule::layouts.master')

@php
    $firstRow = $rows->isNotEmpty() ? $rows->first() : null;
    $firstProviderId = $firstRow['provider_id'] ?? null;
    $firstPreviewUrl = $firstProviderId
        ? route('admin.provider.details.whatsapp.provider_payment_reminder.preview', $firstProviderId)
        : '';
@endphp

@section('title', translate('Pending_provider_balances'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-center gap-3 mb-30">
                <h2 class="page-title">{{ translate('Pending_provider_balances') }}</h2>
            </div>

            <p class="text-muted small mb-4">{{ translate('Pending_provider_balances_help') }}</p>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="{{ route('admin.transaction.pending_provider_balances.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('search') }}</label>
                            <input type="search" name="search" class="form-control theme-input-style" value="{{ $search }}"
                                   placeholder="{{ translate('Pending_provider_balances_search_placeholder') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('Category') }}</label>
                            <select name="category_id" class="form-select theme-input-style">
                                <option value="">{{ translate('all') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected($category_id === (string) $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ translate('sort') }}</label>
                            <select name="sort" class="form-select theme-input-style">
                                <option value="balance_desc" @selected($sort === 'balance_desc')>{{ translate('Pending_provider_balances_sort_balance_high') }}</option>
                                <option value="balance_asc" @selected($sort === 'balance_asc')>{{ translate('Pending_provider_balances_sort_balance_low') }}</option>
                                <option value="name_asc" @selected($sort === 'name_asc')>{{ translate('Pending_provider_balances_sort_name') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn--primary flex-grow-1">{{ translate('search') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @can('provider_update')
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="pk-pending-select-all-page">
                                <label class="form-check-label" for="pk-pending-select-all-page">{{ translate('Select_All') }}</label>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline--primary" id="pk-pending-open-bulk-selected">
                                    {{ translate('Send_reminder_to_selected_providers') }}
                                </button>
                                <button type="button" class="btn btn--primary" id="pk-pending-open-bulk-all"
                                        @if($rows->total() === 0) disabled @endif>
                                    {{ translate('Send_reminder_to_all_listed') }}
                                </button>
                            </div>
                        </div>

                    @endcan

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                @can('provider_update')
                                    <th style="width: 40px;"></th>
                                @endcan
                                <th>{{ translate('Provider') }}</th>
                                <th>{{ translate('Category') }}</th>
                                <th class="text-end">{{ translate('Balance_due_to_collect') }}</th>
                                <th class="text-end">{{ translate('Last_payment_collected') }}</th>
                                <th>{{ translate('Last_payment_collected_at') }}</th>
                                <th style="min-width: 140px;">{{ translate('action') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    @can('provider_update')
                                        <td>
                                            <input type="checkbox" name="provider_ids[]"
                                                   value="{{ $row['provider_id'] }}" class="form-check-input pk-pending-row-cb">
                                        </td>
                                    @endcan
                                    <td>
                                        <a href="{{ route('admin.provider.details', [$row['provider_id'], 'web_page' => 'payment']) }}"
                                           class="fw-semibold">{{ $row['provider_name'] }}</a>
                                    </td>
                                    <td>{{ $row['category_label'] }}</td>
                                    <td class="text-end">{{ with_currency_symbol($row['balance_due']) }}</td>
                                    <td class="text-end">
                                        {{ $row['last_payment_amount'] !== null ? with_currency_symbol($row['last_payment_amount']) : '—' }}
                                    </td>
                                    <td>
                                        @if($row['last_payment_date'])
                                            {{ \Illuminate\Support\Carbon::parse($row['last_payment_date'])->format('Y-m-d') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @can('provider_update')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline--primary text-capitalize pk-pending-open-single-reminder"
                                                        data-preview-url="{{ route('admin.provider.details.whatsapp.provider_payment_reminder.preview', $row['provider_id']) }}"
                                                        data-send-url="{{ route('admin.provider.details.whatsapp.provider_payment_reminder.send', $row['provider_id']) }}">
                                                    {{ translate('Send_payment_reminder_to_provider') }}
                                                </button>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->can('provider_update') ? 7 : 6 }}" class="text-center text-muted py-4">
                                        {{ translate('No Record Found') }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($rows->hasPages())
                        <div class="d-flex justify-content-end mt-3">
                            {!! $rows->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('provider_update')
        {{-- Single provider: preview + confirm (same API as provider Payment tab) --}}
        <div class="modal fade" id="pkPendingSingleReminderModal" tabindex="-1" aria-hidden="true"
             data-default-preview-url="{{ $firstPreviewUrl }}">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Send_payment_reminder_to_provider') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">{{ translate('WhatsApp_payment_reminder_modal_intro') }}</p>
                        <p class="small mb-2"><span class="text-muted">{{ translate('phone') }}:</span>
                            <span class="fw-semibold" id="pk-pending-single-phone">—</span></p>
                        <div class="border rounded p-3 bg-light" style="white-space: pre-wrap; min-height: 4rem;" id="pk-pending-single-preview">{{ translate('Loading…') }}</div>
                        <p class="text-danger small mt-2 d-none" id="pk-pending-single-err"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="button" class="btn btn--primary" id="pk-pending-single-send" disabled>{{ translate('Send') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bulk: selected or all matching filters --}}
        <div class="modal fade" id="pkPendingBulkReminderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Send_payment_reminder_to_provider') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2" id="pk-pending-bulk-intro"></p>
                        <p class="small text-muted mb-2 d-none" id="pk-pending-bulk-sample-heading">{{ translate('Pending_provider_balances_sample_preview') }}</p>
                        <p class="small mb-2 d-none" id="pk-pending-bulk-phone-row"><span class="text-muted">{{ translate('phone') }}:</span>
                            <span class="fw-semibold" id="pk-pending-bulk-phone">—</span></p>
                        <div class="border rounded p-3 bg-light d-none" style="white-space: pre-wrap; min-height: 3rem;" id="pk-pending-bulk-preview"></div>
                        <p class="text-danger small mt-2 d-none" id="pk-pending-bulk-err"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="button" class="btn btn--primary" id="pk-pending-bulk-confirm" disabled>{{ translate('Send') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result --}}
        <div class="modal fade" id="pkPendingWaResultModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" data-role="result-title">{{ translate('WhatsApp_message_sent_modal_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ translate('close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" id="pk-pending-wa-result-message"></p>
                    </div>
                    <div class="modal-footer flex-wrap gap-2">
                        <a href="#" class="btn btn--primary d-none" id="pk-pending-wa-result-chat">{{ translate('WhatsApp_view_chat_inbox') }}</a>
                        <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">{{ translate('close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('script')
    @can('provider_update')
        <script>
            (function () {
                'use strict';
                var sendBatchUrl = @json(route('admin.transaction.pending_provider_balances.send_reminders'));
                var titleOk = @json(translate('WhatsApp_message_sent_modal_title'));
                var titleFail = @json(translate('WhatsApp_message_send_failed_modal_title'));
                var totalFiltered = {{ (int) $rows->total() }};
                var searchVal = @json($search);
                var categoryVal = @json($category_id);
                var sortVal = @json($sort);

                function csrf() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                }

                function parseJsonSafe(r) {
                    return r.text().then(function (t) {
                        try {
                            return { status: r.status, data: JSON.parse(t) };
                        } catch (e) {
                            return { status: r.status, data: { message: t || r.statusText } };
                        }
                    });
                }

                function showResultModal(ok, message, chatUrl, showChat) {
                    var modalEl = document.getElementById('pkPendingWaResultModal');
                    if (!modalEl || typeof bootstrap === 'undefined') return;
                    var titleEl = modalEl.querySelector('[data-role="result-title"]');
                    var msgEl = document.getElementById('pk-pending-wa-result-message');
                    var chatBtn = document.getElementById('pk-pending-wa-result-chat');
                    if (titleEl) titleEl.textContent = ok ? titleOk : titleFail;
                    if (msgEl) msgEl.textContent = message || '';
                    if (chatBtn) {
                        if (ok && showChat && chatUrl) {
                            chatBtn.href = chatUrl;
                            chatBtn.classList.remove('d-none');
                        } else {
                            chatBtn.classList.add('d-none');
                            chatBtn.removeAttribute('href');
                        }
                    }
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }

                /* Select all on page */
                var master = document.getElementById('pk-pending-select-all-page');
                if (master) {
                    master.addEventListener('change', function () {
                        document.querySelectorAll('.pk-pending-row-cb').forEach(function (cb) {
                            cb.checked = master.checked;
                        });
                    });
                }

                /* --- Single reminder modal --- */
                var singleModal = document.getElementById('pkPendingSingleReminderModal');
                var singlePreviewEl = document.getElementById('pk-pending-single-preview');
                var singleErrEl = document.getElementById('pk-pending-single-err');
                var singlePhoneEl = document.getElementById('pk-pending-single-phone');
                var singleSendBtn = document.getElementById('pk-pending-single-send');
                var singlePreviewUrl = '';
                var singleSendUrl = '';

                document.querySelectorAll('.pk-pending-open-single-reminder').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        singlePreviewUrl = btn.getAttribute('data-preview-url') || '';
                        singleSendUrl = btn.getAttribute('data-send-url') || '';
                        if (singleModal && typeof bootstrap !== 'undefined') {
                            bootstrap.Modal.getOrCreateInstance(singleModal).show();
                        }
                    });
                });

                if (singleModal && singlePreviewEl) {
                    singleModal.addEventListener('show.bs.modal', function () {
                        if (singlePreviewEl) singlePreviewEl.textContent = @json(translate('Loading…'));
                        if (singleErrEl) {
                            singleErrEl.textContent = '';
                            singleErrEl.classList.add('d-none');
                        }
                        if (singlePhoneEl) singlePhoneEl.textContent = '—';
                        if (singleSendBtn) singleSendBtn.disabled = true;
                        if (!singlePreviewUrl || typeof fetch === 'undefined') return;
                        fetch(singlePreviewUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'Accept': 'application/json'
                            },
                            body: '{}'
                        }).then(function (r) {
                            return r.json();
                        }).then(function (data) {
                            if (singlePhoneEl) singlePhoneEl.textContent = data.phone || '—';
                            if (data.ok) {
                                if (singlePreviewEl) singlePreviewEl.textContent = data.body || '';
                                if (singleSendBtn) singleSendBtn.disabled = false;
                            } else {
                                if (singlePreviewEl) singlePreviewEl.textContent = '';
                                if (singleErrEl) {
                                    singleErrEl.textContent = data.message || data.error || '';
                                    singleErrEl.classList.remove('d-none');
                                }
                            }
                        }).catch(function () {
                            if (singlePreviewEl) singlePreviewEl.textContent = '';
                            if (singleErrEl) {
                                singleErrEl.textContent = @json(translate('WhatsApp_payment_reminder_failed'));
                                singleErrEl.classList.remove('d-none');
                            }
                        });
                    });
                }

                if (singleSendBtn && singleModal) {
                    singleSendBtn.addEventListener('click', function () {
                        if (!singleSendUrl) return;
                        singleSendBtn.disabled = true;
                        var fd = new FormData();
                        fd.append('_token', csrf());
                        fetch(singleSendUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd,
                            credentials: 'same-origin'
                        }).then(parseJsonSafe).then(function (pack) {
                            var data = pack.data || {};
                            var inst = bootstrap.Modal.getInstance(singleModal);
                            if (inst) inst.hide();
                            showResultModal(!!data.ok, data.message || '', data.chat_url, !!(data.ok && data.show_chat_link));
                        }).catch(function () {
                            var inst = bootstrap.Modal.getInstance(singleModal);
                            if (inst) inst.hide();
                            showResultModal(false, @json(translate('WhatsApp_payment_reminder_failed')), '', false);
                        }).finally(function () {
                            singleSendBtn.disabled = false;
                        });
                    });
                }

                /* --- Bulk modal state --- */
                var bulkModal = document.getElementById('pkPendingBulkReminderModal');
                var bulkIntroEl = document.getElementById('pk-pending-bulk-intro');
                var bulkSampleHead = document.getElementById('pk-pending-bulk-sample-heading');
                var bulkPhoneEl = document.getElementById('pk-pending-bulk-phone');
                var bulkPreviewEl = document.getElementById('pk-pending-bulk-preview');
                var bulkErrEl = document.getElementById('pk-pending-bulk-err');
                var bulkConfirmBtn = document.getElementById('pk-pending-bulk-confirm');
                var bulkMode = 'selected';
                var bulkPreviewUrlForSample = '';

                function resetBulkPreviewUi() {
                    if (bulkSampleHead) bulkSampleHead.classList.add('d-none');
                    var phoneRow = document.getElementById('pk-pending-bulk-phone-row');
                    if (phoneRow) phoneRow.classList.add('d-none');
                    if (bulkPreviewEl) {
                        bulkPreviewEl.classList.add('d-none');
                        bulkPreviewEl.textContent = '';
                    }
                    if (bulkErrEl) {
                        bulkErrEl.textContent = '';
                        bulkErrEl.classList.add('d-none');
                    }
                }

                function loadBulkSamplePreview(url) {
                    if (!url || typeof fetch === 'undefined') {
                        if (bulkConfirmBtn) bulkConfirmBtn.disabled = false;
                        return;
                    }
                    if (bulkPreviewEl) bulkPreviewEl.textContent = @json(translate('Loading…'));
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json'
                        },
                        body: '{}'
                    }).then(function (r) {
                        return r.json();
                    }).then(function (data) {
                        if (bulkSampleHead) bulkSampleHead.classList.remove('d-none');
                        if (bulkPhoneEl) {
                            bulkPhoneEl.textContent = data.phone || '—';
                            var prow = document.getElementById('pk-pending-bulk-phone-row');
                            if (prow) prow.classList.remove('d-none');
                        }
                        if (bulkPreviewEl) {
                            bulkPreviewEl.classList.remove('d-none');
                            bulkPreviewEl.textContent = data.ok ? (data.body || '') : (data.message || '');
                        }
                        if (!data.ok && bulkErrEl) {
                            bulkErrEl.textContent = data.message || '';
                            bulkErrEl.classList.remove('d-none');
                        }
                    }).catch(function () { /* optional sample */ }).finally(function () {
                        if (bulkConfirmBtn) bulkConfirmBtn.disabled = false;
                    });
                }

                var introSelectedTmpl = @json(translate('Pending_provider_balances_bulk_intro_selected'));
                var introAllTmpl = @json(translate('Pending_provider_balances_bulk_intro_all'));

                function openBulkModal(mode) {
                    bulkMode = mode;
                    resetBulkPreviewUi();
                    if (bulkConfirmBtn) bulkConfirmBtn.disabled = true;
                    if (!bulkModal || typeof bootstrap === 'undefined') return;

                    if (mode === 'selected') {
                        var checked = Array.prototype.slice.call(document.querySelectorAll('.pk-pending-row-cb:checked'));
                        if (!checked.length) {
                            alert(@json(translate('Pending_provider_balances_select_one')));
                            return;
                        }
                        if (bulkIntroEl) bulkIntroEl.textContent = introSelectedTmpl.replace(':count', String(checked.length));
                        var first = checked[0];
                        bulkPreviewUrlForSample = first && first.closest
                            ? (function (tr) {
                                var btn = tr.querySelector('.pk-pending-open-single-reminder');
                                return btn ? (btn.getAttribute('data-preview-url') || '') : '';
                            })(first.closest('tr'))
                            : '';
                    } else {
                        if (totalFiltered < 1) {
                            alert(@json(translate('No Record Found')));
                            return;
                        }
                        if (bulkIntroEl) bulkIntroEl.textContent = introAllTmpl.replace(':count', String(totalFiltered));
                        bulkPreviewUrlForSample = @json($firstPreviewUrl);
                    }

                    bootstrap.Modal.getOrCreateInstance(bulkModal).show();

                    if (bulkPreviewUrlForSample) {
                        loadBulkSamplePreview(bulkPreviewUrlForSample);
                    } else if (bulkConfirmBtn) {
                        bulkConfirmBtn.disabled = false;
                    }
                }

                document.getElementById('pk-pending-open-bulk-selected')?.addEventListener('click', function () {
                    openBulkModal('selected');
                });
                document.getElementById('pk-pending-open-bulk-all')?.addEventListener('click', function () {
                    openBulkModal('all_filtered');
                });

                if (bulkConfirmBtn) {
                    bulkConfirmBtn.addEventListener('click', function () {
                        bulkConfirmBtn.disabled = true;
                        var fd = new FormData();
                        fd.append('_token', csrf());
                        fd.append('search', searchVal);
                        fd.append('category_id', categoryVal);
                        fd.append('sort', sortVal);

                        if (bulkMode === 'all_filtered') {
                            fd.append('mode', 'all_filtered');
                        } else {
                            fd.append('mode', 'selected');
                            var checked = document.querySelectorAll('.pk-pending-row-cb:checked');
                            checked.forEach(function (cb) {
                                fd.append('provider_ids[]', cb.value);
                            });
                        }

                        fetch(sendBatchUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf(),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: fd,
                            credentials: 'same-origin'
                        }).then(parseJsonSafe).then(function (pack) {
                            var data = pack.data || {};
                            if (pack.status === 422) {
                                var msg = data.message || '';
                                if (data.errors) {
                                    var firstKey = Object.keys(data.errors)[0];
                                    if (firstKey && data.errors[firstKey][0]) msg = data.errors[firstKey][0];
                                }
                                throw new Error(msg || 'Validation failed');
                            }
                            if (!(pack.status >= 200 && pack.status < 300)) {
                                throw new Error(data.message || 'Request failed');
                            }
                            var bm = document.getElementById('pkPendingBulkReminderModal');
                            if (bm) {
                                var bi = bootstrap.Modal.getInstance(bm);
                                if (bi) bi.hide();
                            }
                            var ok = !!data.ok;
                            showResultModal(ok, data.message || '', '', false);
                        }).catch(function (e) {
                            var bm = document.getElementById('pkPendingBulkReminderModal');
                            if (bm) {
                                var bi = bootstrap.Modal.getInstance(bm);
                                if (bi) bi.hide();
                            }
                            showResultModal(false, (e && e.message) ? e.message : @json(translate('WhatsApp_payment_reminder_failed')), '', false);
                        }).finally(function () {
                            bulkConfirmBtn.disabled = false;
                        });
                    });
                }
            })();
        </script>
    @endcan
@endpush
