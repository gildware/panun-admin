@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $drafts = $payslips->where('status', 'draft')->where('held', false)->count();
    $held = $payslips->where('held', true)->count();
    $net = $payslips->where('status', 'published')->sum('net');
@endphp
<div class="people-ws-head">
    <div>
        <h1>People & HR</h1>
        <p>Company files, holidays, leave, attendance, and pay. An employee still uses My workspace for their own requests.</p>
    </div>
</div>
<div class="people-ws-grid-4">
    <article class="people-ws-card people-ws-stat"><div class="label">People</div><div class="value">{{ $staff->count() }}</div><div class="sub">Active sign-ins</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">Documents missing</div><div class="value">{{ $missingDocuments }}</div><div class="sub">Asked, not uploaded</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">Leave waiting</div><div class="value">{{ $pendingLeave }}</div><div class="sub">Manager decides. HR decides only when no manager is set.</div></article>
    <article class="people-ws-card people-ws-stat"><div class="label">{{ $monthLabel }}</div><div class="value">{{ $workspace->payrollRunLabel($run->status ?? null) }}</div><div class="sub">{{ $drafts }} will be paid · {{ $held }} left out · ₹{{ number_format((float) $net, 0) }} sent</div></article>
</div>
@php
    $actorId = (string) auth()->id();
    $actorIsHr = $workspace->isHr(auth()->user());
    $openLeave = $leaveRequests->where('status', 'pending');
    $hrLeave = $openLeave->filter(function ($leave) use ($profiles) {
        return ! $profiles->get($leave->user_id)?->manager_id;
    });
    $hrSheets = $timesheets->filter(function ($sheet) use ($profiles) {
        return $sheet->status === 'pending' && ! $profiles->get($sheet->user_id)?->manager_id;
    });
@endphp
<article class="people-ws-card people-ws-mt people-ws-scroll">
    <h2>Waiting on HR</h2>
    <p class="people-ws-note">These people have no manager on their file. Everyone else is decided by their manager.</p>
    <table>
        <thead><tr><th>Person</th><th>Request</th><th>When</th><th></th></tr></thead>
        <tbody>
        @foreach($hrLeave as $leave)
            <tr>
                <td>{{ $leave->user ? $workspace->displayName($leave->user) : '—' }}</td>
                <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                <td>{{ $leave->starts_on->format('j M') }}–{{ $leave->ends_on->format('j M') }}</td>
                <td>
                    @if($leave->user && \Modules\AdminModule\Services\PeopleWorkspace::mayDecide($actorId, (string) $leave->user->id, null, $actorIsHr))
                        <form method="post" action="{{ route('admin.people.team.leave.decide', $leave) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="hr-home">
                            <button class="btn-pw good" name="decision" value="approve">Approve</button>
                            <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        @foreach($hrSheets as $sheet)
            <tr>
                <td>{{ $sheet->user ? $workspace->displayName($sheet->user) : '—' }}</td>
                <td>Timesheet</td>
                <td>Week of {{ $sheet->week_starts_on->format('j M') }}</td>
                <td>
                    @if($sheet->user && \Modules\AdminModule\Services\PeopleWorkspace::mayDecide($actorId, (string) $sheet->user->id, null, $actorIsHr))
                        <form method="post" action="{{ route('admin.people.team.timesheet.decide', $sheet) }}">
                            @csrf
                            <input type="hidden" name="return_to" value="hr-home">
                            <button class="btn-pw good" name="decision" value="approve">Approve</button>
                            <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        @if($hrLeave->isEmpty() && $hrSheets->isEmpty())
            <tr><td colspan="4" class="people-ws-note">Nothing is waiting on HR.</td></tr>
        @endif
        </tbody>
    </table>
</article>
