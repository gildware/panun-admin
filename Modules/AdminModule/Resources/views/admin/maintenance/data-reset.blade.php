@extends('adminmodule::layouts.new-master')

@section('title', translate('Reset_Operational_Data'))

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
                                  onsubmit="return confirm('{{ translate('Are_you_sure_you_want_to_clear_all_operational_data_This_cannot_be_undone') }}');">
                                @csrf
                                <input type="hidden" name="reset_form" value="operational">

                                <div class="mb-3">
                                    <label for="confirm" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm"
                                           name="confirm"
                                           class="form-control"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger">
                                    {{ translate('Clear_All_Operational_Data') }}
                                </button>
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
                                  onsubmit="return confirm('{{ translate('Are_you_sure_clear_all_financial_records') }}');">
                                @csrf
                                <input type="hidden" name="reset_form" value="financial">

                                <div class="mb-3">
                                    <label for="confirm_financial" class="form-label">
                                        {{ translate('Type_RESET_to_confirm') }}
                                    </label>
                                    <input type="text"
                                           id="confirm_financial"
                                           name="confirm"
                                           class="form-control"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger">
                                    {{ translate('Clear_all_transactions_and_ledger') }}
                                </button>
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
                                  onsubmit="return confirm('{{ translate('Are_you_sure_clear_selected_WhatsApp_data') }}');">
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
                                           class="form-control"
                                           placeholder="RESET"
                                           required>
                                </div>

                                <button type="submit" class="btn btn--danger">
                                    {{ translate('Clear_selected_WhatsApp_data') }}
                                </button>
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
            var all = document.getElementById('whatsapp_all');
            var cbs = document.querySelectorAll('.whatsapp-scope-cb');
            if (!all || !cbs.length) return;

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
        })();
    </script>
@endpush

