@php $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y'); @endphp
<div class="people-ws-head">
    <div>
        <h1>Attendance</h1>
        <p>Weeks waiting for a decision. Lock {{ $monthLabel }} before you run pay. A locked month cannot take new hours.</p>
    </div>
    <form method="post" action="{{ route('admin.hr.attendance.lock') }}">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <button class="btn-pw primary" type="submit" @disabled($run && $run->attendance_locked)>{{ $run && $run->attendance_locked ? $monthLabel.' locked' : 'Lock '.$monthLabel }}</button>
    </form>
</div>
<form class="people-ws-card" method="get" action="{{ route('admin.accounts.attendance') }}">
    <div class="field"><label for="attendance_period">Month</label><input id="attendance_period" type="month" name="period" value="{{ $period }}" onchange="this.form.submit()"></div>
</form>
<article class="people-ws-card people-ws-mt people-ws-scroll">
    <table>
        <thead><tr><th>Person</th><th>Week of</th><th>Hours</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($timesheets as $sheet)
            @php $hours = collect($sheet->hours ?? [])->sum(); @endphp
            <tr>
                <td>{{ $sheet->user ? $workspace->displayName($sheet->user) : '—' }}</td>
                <td>{{ $sheet->week_starts_on->format('j M Y') }}</td>
                <td>{{ number_format((float) $hours, 1) }}</td>
                <td>@include('adminmodule::admin.people._badge', ['status' => $sheet->status])</td>
                <td>
                    @if($sheet->status === 'pending' && $sheet->user)
                        @php
                            $profile = $profiles->get($sheet->user_id);
                            $canDecide = \Modules\AdminModule\Services\PeopleWorkspace::mayDecide(
                                (string) auth()->id(),
                                (string) $sheet->user->id,
                                $profile?->manager_id ? (string) $profile->manager_id : null,
                                $workspace->isHr(auth()->user())
                            );
                        @endphp
                        @if($canDecide)
                            <form method="post" action="{{ route('admin.people.team.timesheet.decide', $sheet) }}">
                                @csrf
                                <input type="hidden" name="return_to" value="hr-attendance">
                                <button class="btn-pw good" name="decision" value="approve">Approve</button>
                                <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                            </form>
                        @else
                            <span class="people-ws-note">Waiting on {{ $profile?->manager ? $workspace->displayName($profile->manager) : 'their manager' }}</span>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="people-ws-note">No timesheets yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
