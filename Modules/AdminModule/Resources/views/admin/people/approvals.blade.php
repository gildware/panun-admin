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
                    $approvalQuery = [];
                    if ($selectedEmployee !== '') {
                        $approvalQuery['employee'] = $selectedEmployee;
                    }
                    if ($timesheetPeriod === 'last') {
                        $approvalQuery['period'] = 'last';
                    }
                    $periodRanges = [
                        'current' => now()->startOfMonth(),
                        'last' => now()->subMonthNoOverflow()->startOfMonth(),
                    ];
                @endphp
                <div class="people-approval-bar">
                    <nav class="people-ws-tabs people-ws-tabs--counts" aria-label="Approval requests">
                        <a class="{{ $tab === 'leaves' ? 'is-on' : '' }}" href="{{ route('admin.people.approvals', array_merge(['tab' => 'leaves'], $approvalQuery)) }}">
                            Leaves
                            @if($pendingLeave)
                                <span class="people-ws-tab-count">{{ $pendingLeave > 99 ? '99+' : $pendingLeave }}</span>
                            @endif
                        </a>
                        <a class="{{ $tab === 'timesheet' ? 'is-on' : '' }}" href="{{ route('admin.people.approvals', array_merge(['tab' => 'timesheet'], $timesheetPeriod === 'last' ? ['period' => 'last'] : [])) }}">
                            Timesheet
                            @if($pendingTimesheets)
                                <span class="people-ws-tab-count">{{ $pendingTimesheets > 99 ? '99+' : $pendingTimesheets }}</span>
                            @endif
                        </a>
                    </nav>
                    <form class="people-approval-filters" method="get" action="{{ route('admin.people.approvals') }}">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        @if($tab === 'timesheet')
                            <input type="hidden" name="view" value="{{ $employeeWise ? 'employees' : $viewEmployee }}">
                        @endif
                        @if($tab === 'timesheet' && ($employeeWise || $viewEmployee !== ''))
                            <a class="btn-pw people-ts-back" href="{{ route('admin.people.approvals', array_filter(['tab' => 'timesheet', 'period' => $timesheetPeriod === 'last' ? 'last' : null])) }}">
                                <span class="material-icons" aria-hidden="true">arrow_back</span>
                                Back to approval
                            </a>
                        @elseif($tab === 'timesheet')
                            <a class="btn-pw people-ts-view-all" href="{{ route('admin.people.approvals', array_filter(['tab' => 'timesheet', 'view' => 'employees', 'period' => $timesheetPeriod === 'last' ? 'last' : null])) }}">View all employee wise</a>
                        @endif
                        <div class="people-ts-period-field">
                            <label for="approval_period">Time period</label>
                            <select id="approval_period" name="period" onchange="this.form.submit()">
                                @foreach($periodRanges as $key => $start)
                                    <option value="{{ $key }}" @selected($timesheetPeriod === $key)>{{ $start->format('jS F Y') }} – {{ $start->copy()->endOfMonth()->format('jS F Y') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="approval_employee">Employee</label>
                            <select id="approval_employee" name="employee" onchange="var view=this.form.querySelector('[name=view]'); if(view){ view.value=this.value; } this.form.submit();">
                                @if($tab !== 'timesheet')
                                    <option value="">All employees</option>
                                @elseif($viewEmployee === '')
                                    <option value="">Select employee</option>
                                @endif
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected($tab === 'timesheet' ? $viewEmployee === (string) $employee->id : $selectedEmployee === (string) $employee->id)>{{ $workspace->displayName($employee) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                @if($tab === 'leaves')
                    <article class="people-ws-card people-ws-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Applied on</th>
                                    <th>Person</th>
                                    <th>Type</th>
                                    <th>Dates</th>
                                    <th>Duration</th>
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
                                    <td>{{ $leave->created_at?->format('j M Y, g:i A') ?? '—' }}</td>
                                    <td>
                                        @if($leave->user)
                                            <a class="people-ws-person-link" href="{{ route('admin.employee.profile', $leave->user->id) }}" target="_blank" rel="noopener">{{ $workspace->displayName($leave->user) }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                                    <td>{{ $leave->starts_on->format('j M Y') }} – {{ $leave->ends_on->format('j M Y') }}</td>
                                    <td>{{ \Modules\AdminModule\Entities\PeopleLeavePolicy::formatDays((float) $leave->days) }} {{ (float) $leave->days === 1.0 ? 'day' : 'days' }}</td>
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
                                <tr><td colspan="8" class="people-ws-note">No leave requests in this month.</td></tr>
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
                                    <input type="hidden" name="period" value="{{ $timesheetPeriod }}">
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
                                    <input type="hidden" name="period" value="{{ $timesheetPeriod }}">
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
                    @php
                        $failedSheet = old('timesheet_id') ? $submittedTimesheets->firstWhere('id', old('timesheet_id')) : null;
                    @endphp
                    @php
                        $hoursText = fn (float $hours) => \Modules\AdminModule\Services\PeopleWorkspace::hoursText($hours);
                        if (! $failedSheet && old('timesheet_id')) {
                            $reviewSources = $employeeWise ? $employeeReviews : ($timesheetReview ? [$timesheetReview] : []);
                            foreach ($reviewSources as $reviewSource) {
                                foreach ($reviewSource['days'] as $sumDay) {
                                    if ($sumDay['sheet'] && (string) $sumDay['sheet']->id === (string) old('timesheet_id')) {
                                        $failedSheet = $sumDay['sheet'];
                                        break 2;
                                    }
                                }
                            }
                        }
                    @endphp
                    @if($employeeWise)
                        <div class="people-ts-people">
                            @forelse($employeeReviews as $timesheetReview)
                                <h2 class="people-ts-person-name">{{ $timesheetReview['name'] }}</h2>
                                @include('adminmodule::admin.people._timesheet_month', ['timesheetReview' => $timesheetReview, 'employeeId' => $timesheetReview['id'], 'hoursText' => $hoursText])
                            @empty
                                <article class="people-ws-card"><p class="people-ws-note">No employees to show.</p></article>
                            @endforelse
                        </div>
                    @elseif($timesheetReview)
                        @include('adminmodule::admin.people._timesheet_month', ['timesheetReview' => $timesheetReview, 'employeeId' => $selectedEmployee, 'hoursText' => $hoursText])
                    @else
                        <article class="people-ws-card people-ws-scroll people-ts-queue">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Person</th>
                                        <th>Filled on</th>
                                        <th>Filled for</th>
                                        <th>Work detail</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($submittedTimesheetRows as $row)
                                    @php
                                        $sheet = $row['sheet'];
                                        $filledFor = $row['filled_for']->format('l, j F Y');
                                    @endphp
                                    <tr>
                                        <td>{{ $row['person'] }}</td>
                                        <td>{{ $row['filled_on'] ? $row['filled_on']->format('l, j F Y') : '—' }}</td>
                                        <td>{{ $filledFor }}</td>
                                        <td>
                                            @if($row['details'] === [])
                                                —
                                            @else
                                                <div class="people-ts-chips">
                                                    @foreach($row['details'] as $detail)
                                                        <span class="people-ts-chip" title="{{ $detail }}">{{ $detail }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>@include('adminmodule::admin.people._badge', ['status' => $row['state']])</td>
                                        <td class="actions">
                                            <div class="people-ws-actions">
                                                <a class="btn-pw" href="{{ route('admin.people.approvals', ['tab' => 'timesheet', 'employee' => $row['user_id'], 'view' => $row['user_id'], 'period' => $timesheetPeriod]) }}">View all</a>
                                                <button
                                                    class="btn-pw good"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveTimesheetModal"
                                                    data-action="{{ route('admin.people.team.timesheet.decide', $sheet) }}"
                                                    data-employee="{{ $row['user_id'] }}"
                                                    data-timesheet="{{ $sheet->id }}"
                                                    data-summary="Approve {{ $row['person'] }}’s timesheet for {{ $filledFor }}?"
                                                >Approve</button>
                                                <button
                                                    class="btn-pw danger"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectTimesheetModal"
                                                    data-action="{{ route('admin.people.team.timesheet.decide', $sheet) }}"
                                                    data-employee="{{ $row['user_id'] }}"
                                                    data-timesheet="{{ $sheet->id }}"
                                                    data-summary="Reject {{ $row['person'] }}’s timesheet for {{ $filledFor }}?"
                                                >Reject</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="people-ws-note">No timesheets submitted for approval in this month.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </article>
                    @endif
                        <div class="modal fade" id="approveTimesheetModal" tabindex="-1" aria-labelledby="approveTimesheetModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content people-ws-dept-modal">
                                    <form id="approve-timesheet-form" method="post" action="">
                                        @csrf
                                        <input type="hidden" name="return_to" value="approvals-timesheet">
                                        <input type="hidden" name="decision" value="approve">
                                        <input type="hidden" name="timesheet_id" id="approve-timesheet-id" value="">
                                        <input type="hidden" name="employee" id="approve-timesheet-employee" value="">
                                        <input type="hidden" name="period" value="{{ $timesheetPeriod }}">
                                        @if($employeeWise || $viewEmployee !== '')
                                            <input type="hidden" name="view" value="{{ $employeeWise ? 'employees' : $viewEmployee }}">
                                        @endif
                                        <div class="modal-header">
                                            <h2 class="modal-title" id="approveTimesheetModalLabel">Approve timesheet</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="people-ws-note" id="approve-timesheet-summary">Approve this timesheet?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn-pw good" type="submit">Approve</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="rejectTimesheetModal" tabindex="-1" aria-labelledby="rejectTimesheetModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content people-ws-dept-modal">
                                    <form id="reject-timesheet-form" method="post" action="{{ $failedSheet ? route('admin.people.team.timesheet.decide', $failedSheet) : '' }}">
                                        @csrf
                                        <input type="hidden" name="return_to" value="approvals-timesheet">
                                        <input type="hidden" name="decision" value="sent_back">
                                        <input type="hidden" name="timesheet_id" id="reject-timesheet-id" value="{{ $failedSheet->id ?? '' }}">
                                        <input type="hidden" name="employee" id="reject-timesheet-employee" value="{{ $selectedEmployee }}">
                                        <input type="hidden" name="period" value="{{ $timesheetPeriod }}">
                                        @if($employeeWise || $viewEmployee !== '')
                                            <input type="hidden" name="view" value="{{ $employeeWise ? 'employees' : $viewEmployee }}">
                                        @endif
                                        <div class="modal-header">
                                            <h2 class="modal-title" id="rejectTimesheetModalLabel">Reject timesheet</h2>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="people-ws-note" id="reject-timesheet-summary">Reject this timesheet?</p>
                                            @if($errors->has('decision_note'))
                                                <p class="people-ws-note">{{ $errors->first('decision_note') }}</p>
                                            @endif
                                            <div class="field">
                                                <label for="reject-timesheet-note">Reason</label>
                                                <textarea id="reject-timesheet-note" name="decision_note" maxlength="500" required placeholder="Why this timesheet is rejected">{{ old('decision_note') }}</textarea>
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
                                document.querySelectorAll('[data-show-chips]').forEach(function (button) {
                                    button.addEventListener('click', function () {
                                        var chips = button.parentElement;
                                        if (!chips) return;
                                        chips.querySelectorAll('[data-extra-chip]').forEach(function (chip) {
                                            chip.hidden = false;
                                        });
                                        button.remove();
                                    });
                                });

                                function bindTimesheetModal(modalId, formId, summaryId, employeeId, sheetFieldId) {
                                    var modal = document.getElementById(modalId);
                                    if (!modal) return;
                                    modal.addEventListener('show.bs.modal', function (event) {
                                        var button = event.relatedTarget;
                                        var form = document.getElementById(formId);
                                        var summary = document.getElementById(summaryId);
                                        var employee = document.getElementById(employeeId);
                                        var sheet = document.getElementById(sheetFieldId);
                                        if (!button || !form) return;
                                        form.action = button.getAttribute('data-action') || '';
                                        if (summary) summary.textContent = button.getAttribute('data-summary') || summary.textContent;
                                        if (employee) employee.value = button.getAttribute('data-employee') || '';
                                        if (sheet) sheet.value = button.getAttribute('data-timesheet') || '';
                                    });
                                }

                                bindTimesheetModal('approveTimesheetModal', 'approve-timesheet-form', 'approve-timesheet-summary', 'approve-timesheet-employee', 'approve-timesheet-id');
                                bindTimesheetModal('rejectTimesheetModal', 'reject-timesheet-form', 'reject-timesheet-summary', 'reject-timesheet-employee', 'reject-timesheet-id');

                                var rejectTimesheetModal = document.getElementById('rejectTimesheetModal');
                                var rejectTimesheetNote = document.getElementById('reject-timesheet-note');
                                if (rejectTimesheetModal && rejectTimesheetNote) {
                                    rejectTimesheetModal.addEventListener('hidden.bs.modal', function () {
                                        rejectTimesheetNote.value = '';
                                    });
                                    @if($errors->has('decision_note'))
                                    if (window.bootstrap) {
                                        window.bootstrap.Modal.getOrCreateInstance(rejectTimesheetModal).show();
                                    }
                                    @endif
                                }
                            </script>
                        @endpush
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
