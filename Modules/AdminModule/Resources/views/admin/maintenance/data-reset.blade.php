@extends('adminmodule::layouts.new-master')

@section('title', translate('Reset_Operational_Data'))

@push('css_or_js')
    <style>
        .data-reset-timeline li {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .35rem 0;
            border-bottom: 1px dashed rgba(0, 0, 0, .08);
        }

        .data-reset-timeline li:last-child {
            border-bottom: 0;
        }

        .data-reset-timeline .step-icon {
            width: 1.25rem;
            text-align: center;
            flex-shrink: 0;
            line-height: 1.4;
        }

        .data-reset-timeline li.is-active {
            color: var(--bs-danger);
            font-weight: 600;
        }

        .data-reset-timeline li.is-done {
            color: var(--bs-success);
        }

        .data-reset-form.is-busy {
            opacity: .65;
            pointer-events: none;
        }
    </style>
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap d-flex justify-content-between flex-wrap align-items-start gap-3 mb-3">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Reset_Operational_Data') }}</h2>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-danger" role="alert">
                                <h5 class="alert-heading mb-2">{{ translate('This_action_will_permanently_delete_data') }}</h5>
                                <ul class="mb-0 ps-3">
                                    <li>{{ translate('All_ledger_and_transaction_entries_linked_to_operations_will_be_cleared') }}</li>
                                    <li>{{ translate('All_bookings_and_their_related_records_will_be_deleted') }}</li>
                                    <li>{{ translate('All_leads_and_their_followups_will_be_deleted') }}</li>
                                </ul>
                                <p class="mb-0 mt-2">
                                    {{ translate('Use_this_only_when_you_want_to_clear_test_data_and_start_with_fresh_operational_data') }}
                                </p>
                            </div>

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  class="js-data-reset-form"
                                  data-reset-mode="submit"
                                  data-reset-label="{{ translate('Clear_All_Operational_Data') }}"
                                  data-confirm="{{ translate('Are_you_sure_you_want_to_clear_all_operational_data_This_cannot_be_undone') }}">
                                @csrf
                                <input type="hidden" name="reset_form" value="operational">

                                <div class="mb-3">
                                    <label for="confirm" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm"
                                           name="confirm"
                                           class="form-control js-data-reset-confirm"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger js-data-reset-submit">
                                    {{ translate('Clear_All_Operational_Data') }}
                                </button>

                                @include('adminmodule::admin.maintenance.partials._data-reset-progress', ['id' => 'operational'])
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">{{ translate('Clear_transactions_and_ledger_only') }}</h3>
                            <div class="alert alert-danger" role="alert">
                                <p class="mb-2">{{ translate('Clear_transactions_and_ledger_only_description') }}</p>
                                <ul class="mb-0 ps-3">
                                    <li>{{ translate('Deletes_all_ledger_transaction_rows') }}</li>
                                    <li>{{ translate('Deletes_all_transaction_rows') }}</li>
                                    <li>{{ translate('Resets_user_account_balances_to_zero') }}</li>
                                    <li>{{ translate('Does_not_delete_bookings_or_leads') }}</li>
                                </ul>
                            </div>

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  class="js-data-reset-form"
                                  data-reset-mode="submit"
                                  data-reset-label="{{ translate('Clear_all_transactions_and_ledger') }}"
                                  data-confirm="{{ translate('Are_you_sure_clear_all_financial_records') }}">
                                @csrf
                                <input type="hidden" name="reset_form" value="financial">

                                <div class="mb-3">
                                    <label for="confirm_financial" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm_financial"
                                           name="confirm"
                                           class="form-control js-data-reset-confirm"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger js-data-reset-submit">
                                    {{ translate('Clear_all_transactions_and_ledger') }}
                                </button>

                                @include('adminmodule::admin.maintenance.partials._data-reset-progress', ['id' => 'financial'])
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">{{ translate('Delete_all_providers') }}</h3>
                            <div class="alert alert-danger" role="alert">
                                <p class="mb-2">{{ translate('Delete_all_providers_description') }}</p>
                                <ul class="mb-0 ps-3">
                                    <li>{{ translate('Deletes_every_provider_business_and_owner_account') }}</li>
                                    <li>{{ translate('Deletes_provider_bookings_servicemen_and_reviews') }}</li>
                                    <li>{{ translate('Deletes_provider_ledger_transactions_and_wallet_data') }}</li>
                                    <li>{{ translate('Deletes_provider_chats_and_in_app_calls') }}</li>
                                </ul>
                            </div>

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  class="js-data-reset-form"
                                  data-reset-mode="progressive"
                                  data-reset-type="providers"
                                  data-reset-label="{{ translate('Delete_all_providers') }}"
                                  data-confirm="{{ translate('Are_you_sure_delete_all_providers') }}">
                                @csrf
                                <input type="hidden" name="reset_form" value="providers">

                                <div class="mb-3">
                                    <label for="confirm_providers" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm_providers"
                                           name="confirm"
                                           class="form-control js-data-reset-confirm"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger js-data-reset-submit">
                                    {{ translate('Delete_all_providers') }}
                                </button>

                                @include('adminmodule::admin.maintenance.partials._data-reset-progress', ['id' => 'providers'])
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">{{ translate('Delete_all_customers') }}</h3>
                            <div class="alert alert-danger" role="alert">
                                <p class="mb-2">{{ translate('Delete_all_customers_description') }}</p>
                                <ul class="mb-0 ps-3">
                                    <li>{{ translate('Deletes_every_customer_account') }}</li>
                                    <li>{{ translate('Deletes_customer_bookings_and_bid_posts') }}</li>
                                    <li>{{ translate('Deletes_customer_ledger_transactions_and_wallet_data') }}</li>
                                    <li>{{ translate('Deletes_customer_chats_and_in_app_calls') }}</li>
                                </ul>
                                <p class="mb-0 mt-2">{{ translate('Customers_linked_to_provider_businesses_will_be_skipped') }}</p>
                            </div>

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  class="js-data-reset-form"
                                  data-reset-mode="progressive"
                                  data-reset-type="customers"
                                  data-reset-label="{{ translate('Delete_all_customers') }}"
                                  data-confirm="{{ translate('Are_you_sure_delete_all_customers') }}">
                                @csrf
                                <input type="hidden" name="reset_form" value="customers">

                                <div class="mb-3">
                                    <label for="confirm_customers" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm_customers"
                                           name="confirm"
                                           class="form-control js-data-reset-confirm"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger js-data-reset-submit">
                                    {{ translate('Delete_all_customers') }}
                                </button>

                                @include('adminmodule::admin.maintenance.partials._data-reset-progress', ['id' => 'customers'])
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">{{ translate('WhatsApp_operational_data') }}</h3>
                            <div class="alert alert-warning" role="alert">
                                <p class="mb-0">{{ translate('WhatsApp_reset_checkbox_hint') }}</p>
                            </div>

                            @if ($errors->has('whatsapp_scope'))
                                <div class="alert alert-danger">{{ $errors->first('whatsapp_scope') }}</div>
                            @endif

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  id="whatsapp-data-reset-form"
                                  class="js-data-reset-form"
                                  data-reset-mode="submit"
                                  data-reset-label="{{ translate('Clear_selected_WhatsApp_data') }}"
                                  data-confirm="{{ translate('Are_you_sure_clear_selected_WhatsApp_data') }}">
                                @csrf
                                <input type="hidden" name="reset_form" value="whatsapp">

                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_all" value="1"
                                               id="whatsapp_all">
                                        <label class="form-check-label fw-semibold" for="whatsapp_all">
                                            {{ translate('All_WhatsApp_data') }}
                                        </label>
                                    </div>
                                    <div class="ps-3 border-start ms-1">
                                        <div class="form-check">
                                            <input class="form-check-input whatsapp-scope-cb" type="checkbox"
                                                   name="whatsapp_messages" value="1" id="whatsapp_messages">
                                            <label class="form-check-label" for="whatsapp_messages">
                                                {{ translate('WhatsApp_chat_messages_and_AI_logs') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input whatsapp-scope-cb" type="checkbox"
                                                   name="whatsapp_human_support" value="1" id="whatsapp_human_support">
                                            <label class="form-check-label" for="whatsapp_human_support">
                                                {{ translate('Human_support_requests') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input whatsapp-scope-cb" type="checkbox"
                                                   name="whatsapp_provider_leads" value="1" id="whatsapp_provider_leads">
                                            <label class="form-check-label" for="whatsapp_provider_leads">
                                                {{ translate('WhatsApp_provider_leads') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input whatsapp-scope-cb" type="checkbox"
                                                   name="whatsapp_bookings" value="1" id="whatsapp_bookings">
                                            <label class="form-check-label" for="whatsapp_bookings">
                                                {{ translate('WhatsApp_bookings') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input whatsapp-scope-cb" type="checkbox"
                                                   name="whatsapp_users" value="1" id="whatsapp_users">
                                            <label class="form-check-label" for="whatsapp_users">
                                                {{ translate('WhatsApp_users') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_whatsapp" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm_whatsapp"
                                           name="confirm"
                                           class="form-control js-data-reset-confirm"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger js-data-reset-submit">
                                    {{ translate('Clear_selected_WhatsApp_data') }}
                                </button>

                                @include('adminmodule::admin.maintenance.partials._data-reset-progress', ['id' => 'whatsapp'])
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            var csrf = @json(csrf_token());
            var initUrl = @json(route('admin.system-maintenance.data-reset.progress.init'));
            var stepUrl = @json(route('admin.system-maintenance.data-reset.progress.step'));
            var labels = {
                processing: @json(translate('Processing')),
                deletingItem: @json(translate('Deleting_item_of_total')),
                starting: @json(translate('Starting_deletion')),
                nothingToDelete: @json(translate('Nothing_to_delete')),
                completed: @json(translate('Completed')),
                failed: @json(translate('Operation_failed_try_again')),
                working: @json(translate('Please_wait_do_not_close_this_page')),
                connectionLost: @json(translate('Connection_lost_server_may_have_timed_out')),
                serverError: @json(translate('Server_error_please_try_again'))
            };

            var all = document.getElementById('whatsapp_all');
            var cbs = document.querySelectorAll('.whatsapp-scope-cb');
            if (all && cbs.length) {
                function syncFromAll() {
                    var on = all.checked;
                    cbs.forEach(function (cb) { cb.checked = on; });
                }

                function syncAllFromChildren() {
                    var every = true;
                    cbs.forEach(function (cb) { if (!cb.checked) every = false; });
                    all.checked = every;
                }

                all.addEventListener('change', syncFromAll);
                cbs.forEach(function (cb) {
                    cb.addEventListener('change', syncAllFromChildren);
                });
            }

            function allForms() {
                return Array.prototype.slice.call(document.querySelectorAll('.js-data-reset-form'));
            }

            function setBusy(busy) {
                allForms().forEach(function (form) {
                    form.classList.toggle('is-busy', busy);
                    form.querySelectorAll('input, button, select, textarea').forEach(function (el) {
                        el.disabled = busy;
                    });
                });
            }

            function progressRoot(form) {
                var panel = form.querySelector('.data-reset-progress');
                return panel || null;
            }

            function resetProgressPanel(panel) {
                panel.classList.remove('d-none');
                panel.querySelector('.data-reset-timeline').innerHTML = '';
                panel.querySelector('.data-reset-progress-bar').style.width = '0%';
                panel.querySelector('.data-reset-progress-bar').setAttribute('aria-valuenow', '0');
                panel.querySelector('.data-reset-progress-count').textContent = '0 / 0';
                panel.querySelector('.data-reset-progress-title').textContent = labels.processing + '...';
                panel.querySelector('.data-reset-status').textContent = labels.working;
            }

            function appendTimeline(panel, text, state) {
                var list = panel.querySelector('.data-reset-timeline');
                var prev = list.querySelector('li.is-active');
                if (prev) {
                    prev.classList.remove('is-active');
                    prev.classList.add('is-done');
                    prev.querySelector('.step-icon').textContent = '✓';
                }

                var li = document.createElement('li');
                li.className = state || 'is-active';
                var icon = document.createElement('span');
                icon.className = 'step-icon';
                icon.textContent = state === 'is-done' ? '✓' : '…';
                var textNode = document.createElement('span');
                textNode.textContent = text;
                li.appendChild(icon);
                li.appendChild(textNode);
                list.appendChild(li);
                list.scrollTop = list.scrollHeight;
            }

            function updateProgress(panel, current, total) {
                var pct = total > 0 ? Math.min(100, Math.round((current / total) * 100)) : 100;
                panel.querySelector('.data-reset-progress-bar').style.width = pct + '%';
                panel.querySelector('.data-reset-progress-bar').setAttribute('aria-valuenow', String(pct));
                panel.querySelector('.data-reset-progress-count').textContent = current + ' / ' + total;
            }

            function parseJsonResponse(res) {
                return res.text().then(function (text) {
                    var data = null;
                    if (text) {
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            data = null;
                        }
                    }

                    if (!res.ok) {
                        var message = (data && data.message) ? data.message : '';
                        if (!message && text && text.indexOf('Maximum execution time') !== -1) {
                            message = labels.connectionLost;
                        }
                        if (!message && res.status === 419) {
                            message = 'Session expired. Please refresh the page and try again.';
                        }
                        if (!message) {
                            message = res.status >= 500 ? labels.serverError : labels.failed;
                        }
                        throw new Error(message);
                    }

                    if (!data) {
                        throw new Error(labels.serverError);
                    }

                    if (data.ok === false) {
                        throw new Error(data.message || labels.failed);
                    }

                    return data;
                });
            }

            function postJson(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                }).then(parseJsonResponse).catch(function (error) {
                    if (error && error.name === 'TypeError') {
                        throw new Error(labels.connectionLost);
                    }
                    throw error;
                });
            }

            function runProgressive(form, type, confirmValue) {
                var panel = progressRoot(form);
                if (!panel) {
                    return Promise.reject(new Error(labels.failed));
                }

                resetProgressPanel(panel);
                appendTimeline(panel, labels.starting, 'is-active');

                return postJson(initUrl, { type: type, confirm: confirmValue }).then(function (init) {
                    var total = init.total || 0;
                    var skipped = init.skipped || 0;
                    var current = 0;

                    if (total === 0) {
                        updateProgress(panel, 0, 0);
                        appendTimeline(panel, labels.nothingToDelete, 'is-done');
                        panel.querySelector('.data-reset-progress-title').textContent = labels.completed;
                        panel.querySelector('.data-reset-status').textContent = init.type === 'customers' && skipped > 0
                            ? labels.completed + ' (' + skipped + ' skipped)'
                            : labels.completed;
                        return init;
                    }

                    updateProgress(panel, 0, total);

                    function stepLoop() {
                        return postJson(stepUrl, {
                            type: type,
                            total: total,
                            current: current,
                            skipped: skipped
                        }).then(function (result) {
                            current = result.current || current;

                            if (result.label) {
                                var line = labels.deletingItem
                                    .replace(':current', String(current))
                                    .replace(':total', String(total))
                                    .replace(':label', result.label);
                                appendTimeline(panel, line, result.complete ? 'is-done' : 'is-active');
                            }

                            updateProgress(panel, current, total);
                            panel.querySelector('.data-reset-progress-title').textContent =
                                labels.processing + ' (' + current + ' / ' + total + ')';

                            if (result.complete) {
                                panel.querySelector('.data-reset-progress-title').textContent = labels.completed;
                                panel.querySelector('.data-reset-status').textContent = result.message || labels.completed;
                                return result;
                            }

                            return stepLoop();
                        });
                    }

                    return stepLoop();
                });
            }

            function runSubmit(form) {
                var panel = progressRoot(form);
                if (panel) {
                    resetProgressPanel(panel);
                    appendTimeline(panel, labels.starting, 'is-active');
                    updateProgress(panel, 0, 1);
                }

                return fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json, text/html',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form),
                    credentials: 'same-origin'
                }).then(function (res) {
                    if (panel) {
                        updateProgress(panel, 1, 1);
                        appendTimeline(panel, labels.completed, 'is-done');
                        panel.querySelector('.data-reset-progress-title').textContent = labels.completed;
                        panel.querySelector('.data-reset-status').textContent = labels.completed;
                    }

                    if (res.redirected) {
                        window.location.href = res.url;
                        return;
                    }

                    if (!res.ok) {
                        throw new Error(labels.failed);
                    }

                    window.location.reload();
                });
            }

            allForms().forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (form.classList.contains('is-busy')) {
                        return;
                    }

                    var confirmInput = form.querySelector('.js-data-reset-confirm');
                    if (!confirmInput || confirmInput.value !== 'RESET') {
                        var panel = progressRoot(form);
                        if (panel) {
                            resetProgressPanel(panel);
                            appendTimeline(panel, @json(translate('Type_RESET_to_confirm')), 'is-done');
                            panel.querySelector('.data-reset-progress-title').textContent = labels.failed;
                        }
                        return;
                    }

                    setBusy(true);

                    var mode = form.getAttribute('data-reset-mode') || 'submit';
                    var runner;

                    if (mode === 'progressive') {
                        runner = runProgressive(form, form.getAttribute('data-reset-type'), confirmInput.value);
                    } else {
                        runner = runSubmit(form);
                    }

                    runner.catch(function (error) {
                        var panel = progressRoot(form);
                        if (panel) {
                            appendTimeline(panel, error.message || labels.failed, 'is-done');
                            panel.querySelector('.data-reset-progress-title').textContent = labels.failed;
                            panel.querySelector('.data-reset-status').textContent = error.message || labels.failed;
                        }
                    }).then(function (result) {
                        if (mode === 'progressive' && result && result.message) {
                            var panel = progressRoot(form);
                            if (panel) {
                                panel.querySelector('.data-reset-status').textContent = result.message;
                            }
                        }
                    }).finally(function () {
                        setBusy(false);
                    });
                });
            });
        })();
    </script>
@endpush
