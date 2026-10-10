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
    $personId = (string) request()->query('person', '');
    $personRow = null;
    foreach ($rows as $row) {
        if ((string) $row['user_id'] === $personId) {
            $personRow = $row;
            break;
        }
    }
    if ($personRow === null) {
        $personId = '';
    }
    $monthQuery = function (string $forPeriod) use ($personId): array {
        $query = ['period' => $forPeriod];
        if ($personId !== '') {
            $query['person'] = $personId;
        }

        return $query;
    };
    $personWeeks = [];
    if ($personRow) {
        $lead = \Carbon\Carbon::parse($days[0]['date'] ?? $period.'-01')->dayOfWeekIso - 1;
        $slots = array_fill(0, $lead, null);
        foreach ($days as $index => $day) {
            $slots[] = ['day' => $day, 'cell' => $personRow['cells'][$index] ?? ['kind' => 'none', 'mark' => '', 'title' => '', 'editable' => false]];
        }
        while (count($slots) % 7 !== 0) {
            $slots[] = null;
        }
        $personWeeks = array_chunk($slots, 7);
    }
@endphp
<div class="people-ws-head">
    <div>
        <h1>Attendance</h1>
        <p>{{ $personRow ? $personRow['name'].' for '.$monthLabel.'.' : 'One row per employee for '.$monthLabel.'.' }} Lock the month before you run pay. A locked month cannot take new hours.</p>
    </div>
    <div class="people-ws-head-actions">
        @if($attendanceLocked)
            <span class="people-ws-note">{{ $monthLabel }} is locked</span>
            @if(! $run || $run->status !== 'locked')
                <button class="btn-pw" type="button" id="attendance-unlock-open">Unlock</button>
            @endif
        @else
            <form method="post" action="{{ route('admin.hr.attendance.lock') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn-pw primary" type="submit">Lock {{ $monthLabel }}</button>
            </form>
        @endif
    </div>
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
        @if($personRow)
            <p class="people-ws-note people-att-hint">Month view for one employee. Choose Everyone to return to the full list.</p>
        @else
            <p class="people-ws-note people-att-hint">Choose an employee for a month view, or edit one row to set present, absent, or half day. · keeps the automatic mark. A dash is a day before they joined.</p>
        @endif
        </div>
        <div class="people-att-tools">
        <form class="people-att-pick" method="get" action="{{ route('admin.hr.attendance') }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <label class="people-ws-note" for="attendance_person">Employee</label>
            <select id="attendance_person" name="person" onchange="this.form.submit()">
                <option value="">Everyone</option>
                @foreach($rows as $row)
                    <option value="{{ $row['user_id'] }}" @selected($personId === (string) $row['user_id'])>{{ $row['name'] }}@if($row['code'] !== '') ({{ $row['code'] }})@endif</option>
                @endforeach
            </select>
        </form>
        <div class="people-att-month">
            <a class="people-att-nav" href="{{ route('admin.hr.attendance', $monthQuery($prevPeriod)) }}" aria-label="Previous month">
                <span class="material-icons" aria-hidden="true">chevron_left</span>
            </a>
            <form method="get" action="{{ route('admin.hr.attendance') }}">
                @if($personId !== '')
                    <input type="hidden" name="person" value="{{ $personId }}">
                @endif
                <label class="people-ws-note" for="attendance_period">Month</label>
                <input id="attendance_period" type="month" name="period" value="{{ $period }}" onchange="this.form.submit()">
            </form>
            <a class="people-att-nav" href="{{ route('admin.hr.attendance', $monthQuery($nextPeriod)) }}" aria-label="Next month">
                <span class="material-icons" aria-hidden="true">chevron_right</span>
            </a>
        </div>
        </div>
    </div>
    <hr class="people-att-rule">
    @if($editId !== '')
        <form id="attendance-edit" method="post" action="{{ route('admin.hr.attendance.marks') }}">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="hidden" name="user_id" value="{{ $editId }}">
            @if($personId !== '' && $personId === $editId)
                <input type="hidden" name="person" value="{{ $personId }}">
            @endif
        </form>
    @endif
    @if($personRow)
        @php $editing = $editId !== '' && $editId === (string) $personRow['user_id']; @endphp
        <section class="people-att-cal" aria-label="{{ $personRow['name'] }} attendance for {{ $monthLabel }}">
            <div class="people-att-cal-head">
                <div>
                    <strong>{{ $personRow['name'] }}</strong>
                    @if($personRow['code'] !== '')<span>{{ $personRow['code'] }}</span>@endif
                    @if(($personRow['joined'] ?? '') !== '')<span>Joined {{ $personRow['joined'] }}</span>@endif
                </div>
                <ul class="people-att-cal-counts">
                    <li><i class="people-att-mark is-present">P</i> {{ $personRow['counts']['present'] }} present</li>
                    <li><i class="people-att-mark is-absent">A</i> {{ $personRow['counts']['absent'] }} absent</li>
                    <li><i class="people-att-mark is-leave">L</i> {{ $personRow['counts']['leave'] }} leave</li>
                    <li><i class="people-att-mark is-half">HD</i> {{ $personRow['counts']['half'] }} half day</li>
                </ul>
                @if($editing)
                    <div class="people-att-actions">
                        <button class="people-att-edit is-save" type="submit" form="attendance-edit" aria-label="Save" title="Save">
                            <span class="material-icons" aria-hidden="true">check</span>
                        </button>
                        <a class="people-att-edit is-cancel" href="{{ route('admin.hr.attendance', $monthQuery($period)) }}" aria-label="Cancel" title="Cancel">
                            <span class="material-icons" aria-hidden="true">close</span>
                        </a>
                    </div>
                @elseif(! $attendanceLocked)
                    <a class="people-att-edit" href="{{ route('admin.hr.attendance', ['period' => $period, 'person' => $personRow['user_id'], 'edit' => $personRow['user_id']]) }}" aria-label="Edit {{ $personRow['name'] }}" title="Edit">
                        <span class="material-icons" aria-hidden="true">edit</span>
                    </a>
                @endif
            </div>
            <div class="people-att-cal-dows" aria-hidden="true">
                <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span>
            </div>
            <div class="people-att-cal-grid">
                @foreach($personWeeks as $week)
                    @foreach($week as $slot)
                        @if($slot === null)
                            <div class="people-att-cal-cell is-pad"></div>
                        @else
                            @php
                                $day = $slot['day'];
                                $cell = $slot['cell'];
                                $bits = explode(' · ', (string) ($cell['title'] ?? ''), 2);
                                $status = $bits[1] ?? '';
                            @endphp
                            <div class="people-att-cal-cell is-{{ $cell['kind'] }}{{ ! empty($day['today']) ? ' is-today' : '' }}" title="{{ $cell['title'] }}">
                                <span class="people-att-cal-num">{{ $day['day'] }}</span>
                                @if($editing && ! empty($cell['editable']))
                                    <select class="people-att-select is-{{ $cell['kind'] }}" form="attendance-edit" name="marks[{{ $day['date'] }}]" aria-label="{{ $personRow['name'] }} {{ $day['letter'] }} {{ $day['day'] }}" title="· keeps the automatic mark">
                                        <option value="" @selected(($cell['hand'] ?? null) === null)>·</option>
                                        <option value="present" @selected(($cell['hand'] ?? null) === 'present')>P</option>
                                        <option value="absent" @selected(($cell['hand'] ?? null) === 'absent')>A</option>
                                        <option value="half" @selected(($cell['hand'] ?? null) === 'half')>HD</option>
                                    </select>
                                @elseif(($cell['mark'] ?? '') !== '')
                                    <span class="people-att-mark is-{{ $cell['kind'] }}{{ ! empty($cell['hand']) ? ' is-manual' : '' }}">{{ $cell['mark'] }}</span>
                                @endif
                                @if($status !== '')
                                    <span class="people-att-cal-status">{{ $status }}</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </section>
    @else
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
                                <a href="{{ route('admin.hr.attendance', ['period' => $period, 'person' => $row['user_id']]) }}">{{ $row['name'] }}</a>
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
    @endif
</article>
@if($attendanceLocked && (! $run || $run->status !== 'locked'))
<div class="modal fade" id="attendanceUnlockModal" tabindex="-1" aria-labelledby="attendanceUnlockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form method="post" action="{{ route('admin.hr.attendance.unlock') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <div class="modal-header">
                    <h2 class="modal-title" id="attendanceUnlockModalLabel">Unlock attendance</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="people-ws-note">Unlock attendance for {{ $monthLabel }}? Marks can be changed again. Lock the month before you calculate pay.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit">Unlock</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var open = document.getElementById('attendance-unlock-open');
    var modal = document.getElementById('attendanceUnlockModal');
    if (!open || !modal) return;
    open.addEventListener('click', function () {
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();
    });
})();
</script>
@endif
