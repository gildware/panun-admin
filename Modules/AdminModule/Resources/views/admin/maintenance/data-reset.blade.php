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
                                    <li>{{ translate('All_bookings_and_their_related_records_will_be_deleted') }}</li>
                                    <li>{{ translate('All_leads_and_their_followups_will_be_deleted') }}</li>
                                    <li>{{ translate('All_ledger_and_transaction_entries_linked_to_operations_will_be_cleared') }}</li>
                                </ul>
                                <p class="mb-0 mt-2">
                                    {{ translate('Use_this_only_when_you_want_to_clear_test_data_and_start_with_fresh_operational_data') }}
                                </p>
                            </div>

                            <form action="{{ route('admin.system-maintenance.data-reset.run') }}" method="POST"
                                  onsubmit="return confirm('{{ translate('Are_you_sure_you_want_to_clear_all_operational_data_This_cannot_be_undone') }}');">
                                @csrf

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
                </div>
            </div>
        </div>
    </div>
@endsection

