@extends('adminmodule::layouts.new-master')

@section('title', 'Team workspace')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
@php
    $pendingLeave = $leaveRequests->where('status', 'pending');
    $pendingSheets = $timesheets->where('status', 'pending');
    $today = now()->toDateString();
    $outToday = $leaveRequests->filter(function ($leave) use ($today) {
        return $leave->status === 'approved'
            && $leave->starts_on->toDateString() <= $today
            && $leave->ends_on->toDateString() >= $today;
    });
@endphp
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'mode' => 'team',
            'section' => $section,
            'baseUrl' => route('admin.people.team'),
            'links' => [
                'today' => 'Today',
                'leave' => 'Leave requests',
                'timesheets' => 'Timesheets',
                'away' => 'Who is away',
            ],
        ])

        @if($section === 'today')
            <div class="people-ws-head">
                <div>
                    <h1>Your team</h1>
                    <p>{{ $members->count() }} {{ $members->count() === 1 ? 'person' : 'people' }} · {{ now()->format('l, j F Y') }}</p>
                </div>
            </div>
            <div class="people-ws-grid-3">
                <article class="people-ws-card people-ws-stat"><div class="label">Leave waiting on you</div><div class="value">{{ $pendingLeave->count() }}</div><div class="sub">Approve or send back</div></article>
                <article class="people-ws-card people-ws-stat"><div class="label">Timesheets waiting</div><div class="value">{{ $pendingSheets->count() }}</div><div class="sub">Submitted weeks</div></article>
                <article class="people-ws-card people-ws-stat"><div class="label">Out today</div><div class="value">{{ $outToday->count() }}</div><div class="sub">{{ $outToday->isEmpty() ? 'Everyone is in' : $outToday->map(fn ($leave) => $workspace->displayName($leave->user))->join(', ') }}</div></article>
            </div>
            <article class="people-ws-card people-ws-mt people-ws-scroll">
                <h2>Needs a decision</h2>
                <table>
                    <thead><tr><th>Person</th><th>Item</th><th>When</th><th></th></tr></thead>
                    <tbody>
                    @forelse($pendingLeave as $leave)
                        <tr>
                            <td>{{ $workspace->displayName($leave->user) }}</td>
                            <td>{{ $workspace->leaveLabel($leave->leave_type) }} leave</td>
                            <td>{{ $leave->starts_on->format('j M') }}–{{ $leave->ends_on->format('j M') }}</td>
                            <td><a class="btn-pw" href="{{ route('admin.people.team', ['section' => 'leave']) }}">Open</a></td>
                        </tr>
                    @empty
                    @endforelse
                    @foreach($pendingSheets as $sheet)
                        <tr>
                            <td>{{ $workspace->displayName($sheet->user) }}</td>
                            <td>Timesheet</td>
                            <td>Week of {{ $sheet->week_starts_on->format('j M') }}</td>
                            <td><a class="btn-pw" href="{{ route('admin.people.team', ['section' => 'timesheets']) }}">Open</a></td>
                        </tr>
                    @endforeach
                    @if($pendingLeave->isEmpty() && $pendingSheets->isEmpty())
                        <tr><td colspan="4" class="people-ws-note">Nothing is waiting.</td></tr>
                    @endif
                    </tbody>
                </table>
            </article>
        @endif

        @if($section === 'leave')
            <div class="people-ws-head"><div><h1>Leave requests</h1><p>Check the dates against who else is already away. Approving updates the leave balance.</p></div></div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Type</th><th>Dates</th><th>Balance if approved</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($leaveRequests as $leave)
                        @php
                            $balance = $balances->get($leave->user_id);
                            $remaining = $balance ? $balance->remaining($leave->leave_type) : 0;
                            $after = $remaining - $leave->days;
                        @endphp
                        <tr>
                            <td>{{ $workspace->displayName($leave->user) }}</td>
                            <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                            <td>{{ $leave->starts_on->format('j M') }}–{{ $leave->ends_on->format('j M') }} · {{ $leave->days }} {{ $leave->days === 1 ? 'day' : 'days' }}</td>
                            <td>{{ $leave->status === 'pending' ? $after.' of '.($balance ? $balance->allowance($leave->leave_type) : 0).' left' : '—' }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $leave->status])</td>
                            <td>
                                @if($leave->status === 'pending')
                                    <form method="post" action="{{ route('admin.people.team.leave.decide', $leave) }}">
                                        @csrf
                                        <input type="hidden" name="return_to" value="team-leave">
                                        <button class="btn-pw good" name="decision" value="approve" @disabled($after < 0)>Approve</button>
                                        <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="people-ws-note">No leave requests from your team.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
        @endif

        @if($section === 'timesheets')
            <div class="people-ws-head"><div><h1>Timesheets</h1><p>Confirm the hours match the work you saw.</p></div></div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Week</th><th>Hours</th><th>Note</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($timesheets as $sheet)
                        <tr>
                            <td>{{ $workspace->displayName($sheet->user) }}</td>
                            <td>{{ $sheet->week_starts_on->format('j M Y') }}</td>
                            <td>{{ number_format($sheet->totalHours(), 1) }}</td>
                            <td>{{ $sheet->note ?: '—' }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $sheet->status])</td>
                            <td>
                                @if($sheet->status === 'pending')
                                    <form method="post" action="{{ route('admin.people.team.timesheet.decide', $sheet) }}">
                                        @csrf
                                        <button class="btn-pw good" name="decision" value="approve">Approve</button>
                                        <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="people-ws-note">No timesheets yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
        @endif

        @if($section === 'away')
            <div class="people-ws-head"><div><h1>Who is away</h1><p>Approved leave and company holidays. Pay is not on this page.</p></div></div>
            <div class="people-ws-grid-2">
                <article class="people-ws-card">
                    <h2>This team</h2>
                    @php $upcoming = $leaveRequests->filter(fn ($leave) => in_array($leave->status, ['pending', 'approved'], true) && $leave->ends_on->toDateString() >= $today); @endphp
                    @forelse($upcoming as $leave)
                        <div class="people-ws-row">
                            <div><strong>{{ $workspace->displayName($leave->user) }}</strong><div class="people-ws-note">{{ $workspace->leaveLabel($leave->leave_type) }} · {{ $leave->starts_on->format('j M') }}–{{ $leave->ends_on->format('j M') }}</div></div>
                            @include('adminmodule::admin.people._badge', ['status' => $leave->status])
                        </div>
                    @empty
                        <p class="people-ws-note">Nobody on the team has upcoming leave.</p>
                    @endforelse
                </article>
                <article class="people-ws-card">
                    <h2>Company holidays</h2>
                    @forelse($holidays as $holiday)
                        <div class="people-ws-row"><div><strong>{{ $holiday->name }}</strong><div class="people-ws-note">{{ $holiday->holiday_on->format('j F Y') }} · everyone off</div></div></div>
                    @empty
                        <p class="people-ws-note">No upcoming holidays.</p>
                    @endforelse
                </article>
            </div>
        @endif

        @include('adminmodule::admin.people._close')
    </div>
</div>
@endsection
