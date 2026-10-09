@extends('adminmodule::layouts.new-master')

@section('title', 'Approval requests')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="people-ws">
            <div class="people-ws-main">
                @php
                    $approvalQuery = $selectedEmployee !== '' ? ['employee' => $selectedEmployee] : [];
                @endphp
                <div class="people-ws-head people-approval-head">
                    <div>
                        <h1>Approval requests</h1>
                        <p>Leave and timesheets from people who report to you.</p>
                    </div>
                    <form class="people-approval-filter" method="get" action="{{ route('admin.people.approvals') }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <label for="approval_employee">Employee</label>
                        <select id="approval_employee" name="employee" onchange="this.form.submit()">
                            <option value="">All employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" @selected($selectedEmployee === (string) $employee->id)>{{ $workspace->displayName($employee) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <nav class="people-ws-tabs people-ws-tabs--counts" aria-label="Approval requests">
                    <a class="{{ $tab === 'leaves' ? 'is-on' : '' }}" href="{{ route('admin.people.approvals', array_merge(['tab' => 'leaves'], $approvalQuery)) }}">
                        Leaves
                        @if($pendingLeave)
                            <span class="people-ws-tab-count">{{ $pendingLeave > 99 ? '99+' : $pendingLeave }}</span>
                        @endif
                    </a>
                    <a class="{{ $tab === 'timesheet' ? 'is-on' : '' }}" href="{{ route('admin.people.approvals', array_merge(['tab' => 'timesheet'], $approvalQuery)) }}">
                        Timesheet
                        @if($pendingTimesheets)
                            <span class="people-ws-tab-count">{{ $pendingTimesheets > 99 ? '99+' : $pendingTimesheets }}</span>
                        @endif
                    </a>
                </nav>

                @if($tab === 'leaves')
                    <article class="people-ws-card people-ws-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Person</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($leaveRequests as $leave)
                                @php
                                    $balance = $balances->get($leave->user_id);
                                    $tracks = $workspace->leaveTracksBalance($leave->leave_type);
                                    $remaining = $balance ? $balance->remaining($leave->leave_type) : 0;
                                    $blocked = $leave->status === 'pending' && $tracks && $remaining < 0;
                                @endphp
                                <tr>
                                    <td>{{ $leave->user ? $workspace->displayName($leave->user) : '—' }}</td>
                                    <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                                    <td>{{ $leave->starts_on->format('j M Y') }} – {{ $leave->ends_on->format('j M Y') }} · {{ $leave->days }} {{ (float) $leave->days === 1.0 ? 'day' : 'days' }}</td>
                                    <td title="{{ $leave->reason }}">
                                        {{ $leave->reason }}
                                        @if($leave->status === 'sent_back' && filled($leave->decision_note))
                                            <span class="people-decision-note">Rejected: {{ $leave->decision_note }}</span>
                                        @endif
                                    </td>
                                    <td>@include('adminmodule::admin.people._badge', ['status' => $leave->status])</td>
                                    <td>
                                        @if($leave->status === 'pending')
                                            @php
                                                $personName = $leave->user ? $workspace->displayName($leave->user) : 'this person';
                                                $leaveSummary = $personName.'’s '.$workspace->leaveLabel($leave->leave_type).' from '.$leave->starts_on->format('j M Y').' to '.$leave->ends_on->format('j M Y').' ('.$leave->days.' '.((float) $leave->days === 1.0 ? 'day' : 'days').')';
                                            @endphp
                                            <div class="people-ws-actions">
                                                <button
                                                    class="btn-pw good"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveLeaveModal"
                                                    data-action="{{ route('admin.people.team.leave.decide', $leave) }}"
                                                    data-leave="{{ $leave->id }}"
                                                    data-employee="{{ $selectedEmployee }}"
                                                    data-summary="Approve {{ $leaveSummary }}?"
                                                    @disabled($blocked)
                                                    title="{{ $blocked ? 'Not enough leave left' : 'Approve' }}"
                                                >Approve</button>
                                                <button
                                                    class="btn-pw danger"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectLeaveModal"
                                                    data-action="{{ route('admin.people.team.leave.decide', $leave) }}"
                                                    data-leave="{{ $leave->id }}"
                                                    data-employee="{{ $selectedEmployee }}"
                                                    data-summary="Reject {{ $leaveSummary }}?"
                                                >Reject</button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="people-ws-note">{{ $selectedEmployee !== '' ? 'No leave requests for this person.' : 'No leave requests from your team.' }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </article>
                    @php
                        $failedLeave = old('leave_id') ? $leaveRequests->firstWhere('id', old('leave_id')) : null;
                    @endphp
                    <div class="modal fade" id="approveLeaveModal" tabindex="-1" aria-labelledby="approveLeaveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content people-ws-dept-modal">
                                <form id="approve-leave-form" method="post" action="">
                                    @csrf
                                    <input type="hidden" name="return_to" value="approvals-leaves">
                                    <input type="hidden" name="decision" value="approve">
                                    <input type="hidden" name="leave_id" id="approve-leave-id" value="">
                                    <input type="hidden" name="employee" id="approve-leave-employee" value="">
                                    <div class="modal-header">
                                        <h2 class="modal-title" id="approveLeaveModalLabel">Approve leave</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="people-ws-note" id="approve-leave-summary">Approve this leave?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn-pw good" type="submit">Approve</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="rejectLeaveModal" tabindex="-1" aria-labelledby="rejectLeaveModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content people-ws-dept-modal">
                                <form id="reject-leave-form" method="post" action="{{ $failedLeave ? route('admin.people.team.leave.decide', $failedLeave) : '' }}">
                                    @csrf
                                    <input type="hidden" name="return_to" value="approvals-leaves">
                                    <input type="hidden" name="decision" value="sent_back">
                                    <input type="hidden" name="leave_id" id="reject-leave-id" value="{{ $failedLeave->id ?? '' }}">
                                    <input type="hidden" name="employee" id="reject-leave-employee" value="{{ $selectedEmployee }}">
                                    <div class="modal-header">
                                        <h2 class="modal-title" id="rejectLeaveModalLabel">Reject leave</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="people-ws-note" id="reject-leave-summary">{{ $failedLeave ? 'Reject '.($failedLeave->user ? $workspace->displayName($failedLeave->user) : 'this person').'’s '.$workspace->leaveLabel($failedLeave->leave_type).' leave?' : 'Reject this leave?' }}</p>
                                        @if($errors->has('decision_note'))
                                            <p class="people-ws-note">{{ $errors->first('decision_note') }}</p>
                                        @endif
                                        <div class="field">
                                            <label for="reject-leave-note">Reason</label>
                                            <textarea id="reject-leave-note" name="decision_note" maxlength="500" required placeholder="Why this leave is rejected">{{ old('decision_note') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn-pw danger" type="submit">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @push('script')
                        <script>
                            (function () {
                                function bindDecisionModal(modalId, formId, summaryId, employeeId, leaveFieldId) {
                                    var modal = document.getElementById(modalId);
                                    if (!modal) return;
                                    modal.addEventListener('show.bs.modal', function (event) {
                                        var button = event.relatedTarget;
                                        var form = document.getElementById(formId);
                                        var summary = document.getElementById(summaryId);
                                        var employee = document.getElementById(employeeId);
                                        var leave = document.getElementById(leaveFieldId);
                                        if (!button || !form) return;
                                        form.action = button.getAttribute('data-action') || '';
                                        if (summary) summary.textContent = button.getAttribute('data-summary') || summary.textContent;
                                        if (employee) employee.value = button.getAttribute('data-employee') || '';
                                        if (leave) leave.value = button.getAttribute('data-leave') || '';
                                    });
                                }

                                bindDecisionModal('approveLeaveModal', 'approve-leave-form', 'approve-leave-summary', 'approve-leave-employee', 'approve-leave-id');
                                bindDecisionModal('rejectLeaveModal', 'reject-leave-form', 'reject-leave-summary', 'reject-leave-employee', 'reject-leave-id');

                                var rejectModal = document.getElementById('rejectLeaveModal');
                                var rejectNote = document.getElementById('reject-leave-note');
                                if (rejectModal && rejectNote) {
                                    rejectModal.addEventListener('hidden.bs.modal', function () {
                                        rejectNote.value = '';
                                    });
                                    @if($errors->has('decision_note'))
                                    if (window.bootstrap) {
                                        window.bootstrap.Modal.getOrCreateInstance(rejectModal).show();
                                    }
                                    @endif
                                }
                            })();
                        </script>
                    @endpush
                @endif

                @if($tab === 'timesheet')
                    <article class="people-ws-card people-ws-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Person</th>
                                    <th>Week</th>
                                    <th>Hours</th>
                                    <th>Note</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($timesheets as $sheet)
                                <tr>
                                    <td>{{ $sheet->user ? $workspace->displayName($sheet->user) : '—' }}</td>
                                    <td>Week of {{ $sheet->week_starts_on->format('j M Y') }}</td>
                                    <td>{{ number_format($sheet->totalHours(), 1) }}</td>
                                    <td title="{{ $sheet->note }}">{{ $sheet->note ?: '—' }}</td>
                                    <td>@include('adminmodule::admin.people._badge', ['status' => $sheet->status])</td>
                                    <td>
                                        @if($sheet->status === 'pending')
                                            <form class="people-ws-actions" method="post" action="{{ route('admin.people.team.timesheet.decide', $sheet) }}">
                                                @csrf
                                                <input type="hidden" name="return_to" value="approvals-timesheet">
                                                @if($selectedEmployee !== '')
                                                    <input type="hidden" name="employee" value="{{ $selectedEmployee }}">
                                                @endif
                                                <button class="btn-pw good" name="decision" value="approve">Accept</button>
                                                <button class="btn-pw danger" name="decision" value="sent_back">Deny</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="people-ws-note">{{ $selectedEmployee !== '' ? 'No timesheets for this person.' : 'No timesheets from your team.' }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </article>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
