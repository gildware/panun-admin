@extends('adminmodule::layouts.master')

@section('title', translate('Booking_Followups'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                <h2 class="page-title">{{ translate('Booking_Details') }}</h2>
            </div>

            <div class="pb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                        <h3 class="c1">{{ translate('Booking') }} # {{ $booking['readable_id'] }}</h3>
                        <span class="badge badge-{{
                            $booking->booking_status == 'ongoing' ? 'warning' :
                            ($booking->booking_status == 'completed' ? 'success' :
                            ($booking->booking_status == 'canceled' ? 'danger' : 'info'))
                        }}">
                            {{ ucwords($booking->booking_status) }}
                        </span>
                    </div>
                    <p class="opacity-75 fz-12">{{ translate('Booking_Placed') }}
                        : {{ date('d-M-Y h:ia', strtotime($booking->created_at)) }}</p>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('admin.booking.invoice', [$booking->id]) }}" class="btn btn-primary" target="_blank">
                        <span class="material-icons">description</span>{{ translate('Invoice') }}
                    </a>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center flex-xxl-nowrap gap-3 mb-4">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'details' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'details']) }}">{{ translate('details') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'status' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'status']) }}">{{ translate('status') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'followups' ? 'active' : '' }}"
                            href="{{ route('admin.booking.details', [$booking->id, 'web_page' => 'followups']) }}">{{ translate('Followups') }}</a>
                    </li>
                </ul>
            </div>

            <div class="row mb-3 g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body c1-light-bg">
                            <h5 class="mb-2">{{ translate('Next_Follow_up_Date_Provider') }}</h5>
                            @if($booking->provider)
                                <p class="mb-1 fw-semibold">{{ $booking->provider->company_name ?? '' }}</p>
                                <p class="mb-1 small">
                                    <a href="tel:{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '' }}">{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '—' }}</a>
                                </p>
                            @endif
                            @if($nextFollowupProvider ?? null)
                                <p class="mb-0 fw-semibold">{{ $nextFollowupProvider->date->format('d-M-Y h:ia') }}
                                    @if($nextFollowupProvider->reason)
                                        <span class="text-muted">({{ Str::limit($nextFollowupProvider->reason, 60) }})</span>
                                    @endif
                                </p>
                            @else
                                <p class="mb-0 text-muted">—</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body c1-light-bg">
                            <h5 class="mb-2">{{ translate('Next_Follow_up_Date_Customer') }}</h5>
                            @if(($customerName ?? '') || ($customerPhone ?? ''))
                                <p class="mb-1 fw-semibold">{{ ($customerName ?? '') ?: '—' }}</p>
                                <p class="mb-1 small">
                                    @if($customerPhone ?? null)
                                        <a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                                    @else
                                        —
                                    @endif
                                </p>
                            @endif
                            @if($nextFollowupCustomer ?? null)
                                <p class="mb-0 fw-semibold">{{ $nextFollowupCustomer->date->format('d-M-Y h:ia') }}
                                    @if($nextFollowupCustomer->reason)
                                        <span class="text-muted">({{ Str::limit($nextFollowupCustomer->reason, 60) }})</span>
                                    @endif
                                </p>
                            @else
                                <p class="mb-0 text-muted">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="mb-0">{{ translate('Follow_up_History') }}</h4>
                            <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                                <span class="material-icons">add</span>{{ translate('Add_Follow_up') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="text-nowrap">
                                        <tr>
                                            <th>{{ translate('Date_Time') }}</th>
                                            <th>{{ translate('Reason') }}</th>
                                            <th>{{ translate('For') }}</th>
                                            <th>{{ translate('Customer_Info') }}</th>
                                            <th>{{ translate('Provider_Info') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                            <th>{{ translate('Remarks') }}</th>
                                            <th>{{ translate('Reschedule_Reason') }}</th>
                                            <th>{{ translate('Recorded_By') }}</th>
                                            <th class="text-end">{{ translate('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($booking->followups as $followup)
                                            <tr>
                                                <td>{{ $followup->date->format('d-M-Y h:ia') }}</td>
                                                <td>{{ $followup->reason ?: '—' }}</td>
                                                <td>{{ translate(ucfirst($followup->for)) }}</td>
                                                <td>
                                                    @if($booking->customer)
                                                        <div>{{ trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? '')) ?: '—' }}</div>
                                                        <div class="small"><a href="tel:{{ $booking->customer->phone ?? '' }}">{{ $booking->customer->phone ?? '—' }}</a></div>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($booking->provider)
                                                        <div>{{ $booking->provider->company_name ?? '—' }}</div>
                                                        <div class="small"><a href="tel:{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '' }}">{{ $booking->provider->contact_person_phone ?? $booking->provider->company_phone ?? '—' }}</a></div>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $followup->status == 'completed' ? 'success' : ($followup->status == 'cancelled' ? 'danger' : ($followup->status == 'rescheduled' ? 'warning' : 'info')) }}">
                                                        {{ translate(ucfirst($followup->status)) }}
                                                    </span>
                                                </td>
                                                <td class="text-break">{{ Str::limit($followup->remarks, 80) ?: '—' }}</td>
                                                <td class="text-break">{{ $followup->status === 'rescheduled' && $followup->reschedule_reason ? Str::limit($followup->reschedule_reason, 60) : '—' }}</td>
                                                <td>
                                                    @if($followup->createdBy)
                                                        {{ $followup->createdBy->first_name }} {{ $followup->createdBy->last_name }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($followup->status === 'scheduled')
                                                        <button type="button" class="btn btn-sm btn--primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#takeFollowupModal--{{ $followup->id }}"
                                                                data-followup-id="{{ $followup->id }}">
                                                            {{ translate('Take_Follow_up') }}
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>

                                            @if($followup->status === 'scheduled')
                                                <div class="modal fade" id="takeFollowupModal--{{ $followup->id }}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="post" id="followup-update-form--{{ $followup->id }}" action="{{ route('admin.booking.followup.update', [$booking->id, $followup->id]) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">{{ translate('Take_Follow_up') }} — {{ $followup->date->format('d-M-Y h:ia') }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="text-muted small">{{ translate('For') }}: {{ translate(ucfirst($followup->for)) }}
                                                                        @if($followup->reason) — {{ $followup->reason }} @endif
                                                                    </p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">{{ translate('Status') }}</label>
                                                                        <select name="status" class="form-select followup-status-select" required data-modal-id="takeFollowupModal--{{ $followup->id }}">
                                                                            <option value="completed">{{ translate('Completed') }}</option>
                                                                            <option value="rescheduled">{{ translate('Rescheduled') }}</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3 reschedule-fields-wrap" id="reschedule-fields-wrap--{{ $followup->id }}" style="display: none;">
                                                                        <label class="form-label">{{ translate('New_Date_Time') }} <span class="text-danger">*</span></label>
                                                                        <input type="datetime-local" name="reschedule_date" class="form-control reschedule-date-input mb-2" value="{{ $followup->date->format('Y-m-d\TH:i') }}">
                                                                        <label class="form-label">{{ translate('Reschedule_Reason') }} <span class="text-danger">*</span></label>
                                                                        <textarea name="reschedule_reason" class="form-control reschedule-reason-input" rows="2" placeholder="{{ translate('Reason_for_reschedule') }}" maxlength="500"></textarea>
                                                                    </div>
                                                                    <div class="mb-3 completed-remarks-wrap">
                                                                        <label class="form-label">{{ translate('Remarks') }} <span class="text-danger">*</span></label>
                                                                        <textarea name="remarks" class="form-control remarks-input" rows="4" placeholder="{{ translate('Add_remarks_from_follow_up') }}">{{ $followup->remarks }}</textarea>
                                                                    </div>
                                                                    <div class="mb-3 add-another-wrap" id="add-another-wrap--{{ $followup->id }}" style="display: none;">
                                                                        <div class="form-check mb-2">
                                                                            <input type="checkbox" class="form-check-input add-another-checkbox" name="add_another_followup" value="1" id="add_another--{{ $followup->id }}" data-followup-id="{{ $followup->id }}">
                                                                            <label class="form-check-label" for="add_another--{{ $followup->id }}">{{ translate('Schedule_another_follow_up') }}</label>
                                                                        </div>
                                                                        <div class="add-another-fields border rounded p-3 bg-light" id="add-another-fields--{{ $followup->id }}" style="display: none;">
                                                                            <div class="mb-2">
                                                                                <label class="form-label">{{ translate('Date_Time') }} <span class="text-danger">*</span></label>
                                                                                <input type="datetime-local" name="add_another_date" class="form-control">
                                                                            </div>
                                                                            <div class="mb-2">
                                                                                <label class="form-label">{{ translate('Reason') }}</label>
                                                                                <input type="text" name="add_another_reason" class="form-control" maxlength="500">
                                                                            </div>
                                                                            <div>
                                                                                <label class="form-label">{{ translate('For') }} <span class="text-danger">*</span></label>
                                                                                <select name="add_another_for" class="form-select">
                                                                                    <option value="customer" {{ $followup->for === 'customer' ? 'selected' : '' }}>{{ translate('Customer') }}</option>
                                                                                    <option value="provider" {{ $followup->for === 'provider' ? 'selected' : '' }}>{{ translate('Provider') }}</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                                                                    <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">{{ translate('No_follow_ups_yet') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Follow-up Modal --}}
    <div class="modal fade" id="addFollowupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('admin.booking.followup.store', $booking->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Add_Follow_up') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Date_Time') }} <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Reason') }}</label>
                            <input type="text" name="reason" class="form-control" placeholder="{{ translate('Reason_for_follow_up') }}" maxlength="500">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ translate('For') }} <span class="text-danger">*</span></label>
                            <select name="for" class="form-select" required>
                                <option value="customer">{{ translate('Customer') }}</option>
                                <option value="provider">{{ translate('Provider') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('cancel') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            function toggleTakeFollowupModal(followupId, status) {
                var modal = document.getElementById('takeFollowupModal--' + followupId);
                if (!modal) return;
                var rescheduleWrap = document.getElementById('reschedule-fields-wrap--' + followupId);
                var addAnotherWrap = document.getElementById('add-another-wrap--' + followupId);
                var remarksInput = modal.querySelector('.remarks-input');
                if (remarksInput) {
                    if (status === 'completed') remarksInput.setAttribute('required', 'required');
                    else remarksInput.removeAttribute('required');
                }
                if (rescheduleWrap) {
                    var dateInput = rescheduleWrap.querySelector('.reschedule-date-input');
                    var reasonInput = rescheduleWrap.querySelector('.reschedule-reason-input');
                    if (status === 'rescheduled') {
                        rescheduleWrap.style.display = 'block';
                        if (dateInput) dateInput.setAttribute('required', 'required');
                        if (reasonInput) reasonInput.setAttribute('required', 'required');
                    } else {
                        rescheduleWrap.style.display = 'none';
                        if (dateInput) { dateInput.removeAttribute('required'); dateInput.value = ''; }
                        if (reasonInput) { reasonInput.removeAttribute('required'); reasonInput.value = ''; }
                    }
                }
                if (addAnotherWrap) {
                    addAnotherWrap.style.display = status === 'completed' ? 'block' : 'none';
                    var checkbox = addAnotherWrap.querySelector('.add-another-checkbox');
                    var fields = document.getElementById('add-another-fields--' + followupId);
                    if (checkbox) checkbox.checked = false;
                    if (fields) { fields.style.display = 'none'; fields.querySelectorAll('input, select').forEach(function (el) { el.removeAttribute('required'); }); }
                }
            }
            document.querySelectorAll('.followup-status-select').forEach(function (select) {
                select.addEventListener('change', function () {
                    var modalId = this.getAttribute('data-modal-id');
                    var followupId = modalId.replace('takeFollowupModal--', '');
                    toggleTakeFollowupModal(followupId, this.value);
                });
            });
            document.querySelectorAll('.add-another-checkbox').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    var followupId = this.getAttribute('data-followup-id');
                    var fields = document.getElementById('add-another-fields--' + followupId);
                    if (!fields) return;
                    if (this.checked) {
                        fields.style.display = 'block';
                        var dateInput = fields.querySelector('input[name="add_another_date"]');
                        var forSelect = fields.querySelector('select[name="add_another_for"]');
                        if (dateInput) dateInput.setAttribute('required', 'required');
                        if (forSelect) forSelect.setAttribute('required', 'required');
                    } else {
                        fields.style.display = 'none';
                        fields.querySelectorAll('input, select').forEach(function (el) { el.removeAttribute('required'); el.value = ''; });
                    }
                });
            });
            document.querySelectorAll('[id^="takeFollowupModal--"]').forEach(function (modal) {
                if (!modal.id || !modal.id.startsWith('takeFollowupModal--')) return;
                var followupId = modal.id.replace('takeFollowupModal--', '');
                modal.addEventListener('show.bs.modal', function () {
                    var select = modal.querySelector('.followup-status-select');
                    if (select) toggleTakeFollowupModal(followupId, select.value);
                });
            });
            document.querySelectorAll('form[id^="followup-update-form--"]').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var status = form.querySelector('.followup-status-select');
                    if (status && status.value === 'completed') {
                        var remarks = form.querySelector('.remarks-input');
                        if (remarks && !remarks.value.trim()) { remarks.focus(); return false; }
                    }
                    var addAnother = form.querySelector('.add-another-checkbox');
                    if (addAnother && addAnother.checked) {
                        var dateInput = form.querySelector('input[name="add_another_date"]');
                        if (dateInput && !dateInput.value) { dateInput.focus(); return false; }
                    }
                    return true;
                });
            });
        })();
    </script>
@endpush
