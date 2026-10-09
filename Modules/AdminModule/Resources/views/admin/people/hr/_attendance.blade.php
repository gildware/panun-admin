@php
    $monthLabel = \Carbon\Carbon::parse($period.'-01')->format('F Y');
    $monthCursor = \Carbon\Carbon::parse($period.'-01')->startOfMonth();
    $prevPeriod = $monthCursor->copy()->subMonth()->format('Y-m');
    $nextPeriod = $monthCursor->copy()->addMonth()->format('Y-m');
    $grid = $attendance ?? ['days' => [], 'rows' => []];
    $days = $grid['days'] ?? [];
    $rows = $grid['rows'] ?? [];
    $attendanceLocked = (bool) ($run && $run->attendance_locked);
    $editId = $attendanceLocked ? '' : (string) request()->query('edit', '');
@endphp
<div class="people-ws-head">
    <div>
        <h1>Attendance</h1>
        <p>One row per employee for {{ $monthLabel }}. Lock the month before you run pay. A locked month cannot take new hours.</p>
    </div>
    <form method="post" action="{{ route('admin.hr.attendance.lock') }}">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <button class="btn-pw primary" type="submit" @disabled($run && $run->attendance_locked)>{{ $run && $run->attendance_locked ? $monthLabel.' locked' : 'Lock '.$monthLabel }}</button>
    </form>
</div>
<article class="people-ws-card">
    <div class="people-att-bar">
        <div>
        <ul class="people-att-legend">
            <li><i class="people-att-mark is-present">P</i> Present</li>
            <li><i class="people-att-mark is-absent">A</i> Absent</li>
            <li><i class="people-att-mark is-leave">L</i> Leave</li>
            <li><i class="people-att-mark is-half">HD</i> Half day</li>
            <li><i class="people-att-mark is-pending">L</i> Pending</li>
            <li><i class="people-att-mark is-off">WO</i> Week off</li>
            <li><i class="people-att-mark is-holiday">H</i> Holiday</li>
        </ul>
        <p class="people-ws-note people-att-hint">Edit one employee to set present, absent, or half day. · keeps the automatic mark. A dash is a day before they joined.</p>
        </div>
        <div class="people-att-tools">
        <div class="people-att-month">
            <a class="people-att-nav" href="{{ route('admin.hr.attendance', ['period' => $prevPeriod]) }}" aria-label="Previous month">
                <span class="material-icons" aria-hidden="true">chevron_left</span>
            </a>
            <form method="get" action="{{ route('admin.hr.attendance') }}">
                <label class="people-ws-note" for="attendance_period">Month</label>
                <input id="attendance_period" type="month" name="period" value="{{ $period }}" onchange="this.form.submit()">
            </form>
            <a class="people-att-nav" href="{{ route('admin.hr.attendance', ['period' => $nextPeriod]) }}" aria-label="Next month">
                <span class="material-icons" aria-hidden="true">chevron_right</span>
            </a>
        </div>
        </div>
    </div>
    @if($editId !== '')
        <form id="attendance-edit" method="post" action="{{ route('admin.hr.attendance.marks') }}">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="user_id" value="{{ $editId }}">
        </form>
    @endif
    <div class="people-att-wrap">
        <table class="people-att" aria-label="Attendance for {{ $monthLabel }}">
            <thead>
                <tr>
                    <th class="people-att-who" scope="col">Employee</th>
                    @foreach($days as $day)
                        <th class="people-att-day{{ $day['today'] ? ' is-today' : '' }}{{ $day['off'] ? ' is-off' : '' }}" scope="col">
                            <span class="people-att-dow">{{ $day['letter'] }}</span>
                            <span class="people-att-num">{{ $day['day'] }}</span>
                        </th>
                    @endforeach
                    <th class="people-att-sum is-p" scope="col" title="Present">P</th>
                    <th class="people-att-sum is-a" scope="col" title="Absent">A</th>
                    <th class="people-att-sum is-l" scope="col" title="Leave">L</th>
                    <th class="people-att-sum is-hd" scope="col" title="Half day">HD</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php $editing = $editId !== '' && $editId === (string) $row['user_id']; @endphp
                <tr>
                    <th class="people-att-who" scope="row">
                        <div class="people-att-person">
                            <div>
                                <strong>{{ $row['name'] }}</strong>
                                @if($row['code'] !== '')<span>{{ $row['code'] }}</span>@endif
                                @if(($row['joined'] ?? '') !== '')<span>Joined {{ $row['joined'] }}</span>@endif
                            </div>
                            @if($editing)
                                <div class="people-att-actions">
                                    <button class="people-att-edit is-save" type="submit" form="attendance-edit" aria-label="Save" title="Save">
                                        <span class="material-icons" aria-hidden="true">check</span>
                                    </button>
                                    <a class="people-att-edit is-cancel" href="{{ route('admin.hr.attendance', ['period' => $period]) }}" aria-label="Cancel" title="Cancel">
                                        <span class="material-icons" aria-hidden="true">close</span>
                                    </a>
                                </div>
                            @elseif(! $attendanceLocked)
                                <a class="people-att-edit" href="{{ route('admin.hr.attendance', ['period' => $period, 'edit' => $row['user_id']]) }}" aria-label="Edit {{ $row['name'] }}" title="Edit">
                                    <span class="material-icons" aria-hidden="true">edit</span>
                                </a>
                            @endif
                        </div>
                    </th>
                    @foreach($row['cells'] as $index => $cell)
                        <td class="people-att-day{{ ! empty($days[$index]['today']) ? ' is-today' : '' }}" title="{{ $cell['title'] }}">
                            @if($editing && ! empty($cell['editable']))
                                <select class="people-att-select is-{{ $cell['kind'] }}" form="attendance-edit" name="marks[{{ $days[$index]['date'] }}]" aria-label="{{ $row['name'] }} {{ $days[$index]['letter'] }} {{ $days[$index]['day'] }}" title="· keeps the automatic mark">
                                    <option value="" @selected(($cell['hand'] ?? null) === null)>·</option>
                                    <option value="present" @selected(($cell['hand'] ?? null) === 'present')>P</option>
                                    <option value="absent" @selected(($cell['hand'] ?? null) === 'absent')>A</option>
                                    <option value="half" @selected(($cell['hand'] ?? null) === 'half')>HD</option>
                                </select>
                            @elseif($cell['mark'] !== '')
                                <span class="people-att-mark is-{{ $cell['kind'] }}{{ ! empty($cell['hand']) ? ' is-manual' : '' }}">{{ $cell['mark'] }}</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="people-att-sum is-p">{{ $row['counts']['present'] }}</td>
                    <td class="people-att-sum is-a">{{ $row['counts']['absent'] }}</td>
                    <td class="people-att-sum is-l">{{ $row['counts']['leave'] }}</td>
                    <td class="people-att-sum is-hd">{{ $row['counts']['half'] }}</td>
                </tr>
            @empty
                <tr><td class="people-ws-note" colspan="{{ count($days) + 5 }}">No employees for {{ $monthLabel }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</article>
