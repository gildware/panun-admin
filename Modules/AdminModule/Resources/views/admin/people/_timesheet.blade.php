@php
    $board = $timesheetBoard;
    $fmt = function (float $hours, bool $minutes = false): string {
        $total = (int) round($hours * 60);
        $h = intdiv($total, 60);
        $m = $total % 60;
        if (! $minutes && $m === 0) {
            return $h.'h';
        }

        return $h.'h '.sprintf('%02d', $m).'m';
    };
    $pendingCount = $board['lastMonthPending'] > 0 ? $board['lastMonthPending'] : $board['pendingTotal'];
    $pendingCopy = $board['lastMonthPending'] > 0
        ? 'You have '.$board['lastMonthPending'].' pending timesheet '.($board['lastMonthPending'] === 1 ? 'day' : 'days').' for last month.'
        : 'You have '.$board['pendingTotal'].' pending timesheet '.($board['pendingTotal'] === 1 ? 'day' : 'days').'.';
    $monthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $board['month'].'-01')->startOfDay();
    $gridStart = $monthStart->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $gridEnd = $monthStart->copy()->endOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(6);
    $cardsByDate = [];
    foreach ($board['cards'] as $card) {
        $cardsByDate[(string) $card['date']] = $card;
    }
    $weekMeta = [];
    $weekCursor = $gridStart->copy();
    $weekNumber = 1;
    while ($weekCursor->lte($gridEnd)) {
        $weekStart = $weekCursor->copy();
        $weekEnd = $weekCursor->copy()->addDays(6);
        $weekCards = [];
        for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay()) {
            $key = $day->toDateString();
            if (isset($cardsByDate[$key])) {
                $weekCards[] = $cardsByDate[$key];
            }
        }
        $weekMeta[] = [
            'number' => $weekNumber,
            'title' => 'Week '.$weekNumber,
            'range' => $weekStart->month === $weekEnd->month
                ? $weekStart->format('j').'–'.$weekEnd->format('j M')
                : $weekStart->format('j M').'–'.$weekEnd->format('j M'),
            'start' => $weekStart->toDateString(),
            'end' => $weekEnd->toDateString(),
            'cards' => $weekCards,
        ];
        $weekCursor->addWeek();
        $weekNumber++;
    }
    $weekCount = count($weekMeta);
    $weekPageCount = max(1, $weekCount);
    $oldWorkDate = old('work_date');
    $hasOldDay = is_string($oldWorkDate) && isset($cardsByDate[$oldWorkDate]);
    $autoOpenDate = null;
    if (! $hasOldDay) {
        $todayKey = now()->toDateString();
        $dateKeys = array_keys($cardsByDate);
        sort($dateKeys);
        $firstPending = null;
        foreach ($dateKeys as $dateKey) {
            $candidate = $cardsByDate[$dateKey];
            if (! empty($candidate['outside']) || empty($candidate['open'])) {
                continue;
            }
            $candidateState = (string) ($candidate['state'] ?? '');
            if (in_array($candidateState, ['holiday', 'future', 'off', 'before', 'weekoff'], true)) {
                continue;
            }
            if ($firstPending === null && $candidateState === 'pending') {
                $firstPending = $dateKey;
            }
            if ($dateKey === $todayKey) {
                $autoOpenDate = $todayKey;
                break;
            }
        }
        if ($autoOpenDate === null) {
            $autoOpenDate = $firstPending;
        }
    }
    $focusDate = $hasOldDay ? $oldWorkDate : $autoOpenDate;
    $weekPage = 0;
    if ($focusDate !== null) {
        foreach ($weekMeta as $index => $week) {
            if ($focusDate >= $week['start'] && $focusDate <= $week['end']) {
                $weekPage = $index;
                break;
            }
        }
    }
    $weekPage = min($weekPage, $weekPageCount - 1);
    $labelFrom = $weekPage;
    $labelTo = $weekPage;
    $rangeText = function (string $start, string $end): string {
        $from = \Carbon\Carbon::parse($start);
        $to = \Carbon\Carbon::parse($end);
        if ($from->isSameDay($to)) {
            return $from->format('j M');
        }
        if ($from->month === $to->month && $from->year === $to->year) {
            return $from->format('j').'–'.$to->format('j M');
        }
        if ($from->year === $to->year) {
            return $from->format('j M').'–'.$to->format('j M');
        }

        return $from->format('j M Y').'–'.$to->format('j M Y');
    };
    $weekLabel = $weekCount === 0 ? '' : $rangeText($weekMeta[$labelFrom]['start'], $weekMeta[$labelTo]['end']);
@endphp
<div class="ts-app" id="ts-app" data-min-hours="{{ $board['minHours'] ?? 0 }}">
    <div class="ts-main">
        <section class="ts-panel" aria-label="Timesheet">
            <header class="ts-toolbar">
                <h2><mark>Timesheet</mark></h2>
                <label class="ts-month">
                    <span class="ts-sr">Month</span>
                    <select id="ts-month" aria-label="Month">
                        @foreach($board['months'] as $option)
                            <option value="{{ $option['value'] }}" @selected($option['value'] === $board['month'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="ts-head-stats">
                    <p class="ts-info-pill is-filled" title="Hours logged on tasks this month">
                        <strong id="ts-filled">{{ $fmt($board['filled'] ?? $board['total']) }}</strong>
                        <span>Filled hours</span>
                    </p>
                    <p class="ts-info-pill is-leave" title="Hours marked as leave this month">
                        <strong id="ts-leave-hours">{{ $fmt($board['leaveTotal'] ?? 0) }}</strong>
                        <span>Leave hours</span>
                    </p>
                </div>
                <button class="ts-leave-btn" type="button" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">Apply leave</button>
                <div class="ts-tools">
                    <div class="ts-week-bar" data-week-page="{{ $weekPage }}" data-week-count="{{ $weekCount }}" data-today="{{ now()->toDateString() }}">
                        <div class="ts-view" role="group" aria-label="Timesheet view">
                            <button type="button" data-view="1" class="is-on">Weekly</button>
                            <button type="button" data-view="2">Bi-weekly</button>
                            <button type="button" data-view="0">Monthly</button>
                        </div>
                        <p class="ts-week-bar-label" data-week-label>{{ $weekLabel }}</p>
                        <div class="ts-week-arrows">
                            <button type="button" class="ts-week-arrow" data-week-step="-1" aria-label="Previous" @disabled($weekPage === 0)>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="ts-week-arrow" data-week-step="1" aria-label="Next" @disabled($weekPage >= $weekPageCount - 1)>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="ts-days" id="ts-days">
                @if($weekMeta === [])
                    <p class="ts-empty">No working days to fill in {{ $board['monthLabel'] }}.</p>
                @else
                <div class="ts-weeks is-one" style="--ts-week-cols: {{ max(1, $weekCount) }}">
                @foreach($weekMeta as $week)
                <section class="ts-week{{ ($week['number'] - 1) === $weekPage ? '' : ' is-off' }}" data-week="{{ $week['number'] }}" data-start="{{ $week['start'] }}" data-end="{{ $week['end'] }}" aria-label="{{ $week['range'] }}">
                    <header class="ts-week-head">
                        <strong>{{ $week['range'] }}</strong>
                    </header>
                @forelse($week['cards'] as $card)
                    @php
                        $shortLabel = \Carbon\Carbon::parse($card['date'])->format('j M, l');
                    @endphp
                    @if(! empty($card['outside']))
                    <div class="ts-day-skeleton" title="{{ $card['label'] }}">{{ $shortLabel }}</div>
                    @else
                    @php
                        $open = $card['open'];
                        $useOld = $open && old('work_date') === $card['date'] && is_array(old('rows'));
                        $rows = $useOld ? old('rows') : ($card['rows'] ?: [[]]);
                        $rowHours = 0.0;
                        $taskCount = 0;
                        foreach ($rows as $row) {
                            $amount = (float) ($row['hours'] ?? 0);
                            $rowHours += $amount;
                            if (trim((string) ($row['task'] ?? '')) !== '' && $amount > 0) {
                                $taskCount++;
                            }
                        }
                        $dayState = (string) ($card['state'] ?? 'pending');
                        $closed = in_array($dayState, ['holiday', 'future', 'off', 'before'], true);
                        $startOpen = ($useOld && ! $closed) || ($autoOpenDate === $card['date'] && ! $closed && $open);
                        $minHours = (float) ($board['minHours'] ?? 0);
                        $submitHint = '';
                        if ($taskCount < 1) {
                            $submitHint = $minHours > 0
                                ? 'Add a task and at least '.$fmt($minHours).'.'
                                : 'Add a task with hours.';
                        } elseif ($rowHours > 24) {
                            $submitHint = 'A day cannot be more than 24h.';
                        } elseif ($minHours > 0 && $rowHours + 0.001 < $minHours) {
                            $submitHint = 'A day needs at least '.$fmt($minHours).'.';
                        }
                        $holidayLabel = trim((string) ($card['holidayName'] ?? ''));
                        $holidayLabel = $holidayLabel !== '' ? 'Holiday · '.$holidayLabel : 'Holiday';
                        $clockFace = function (string $hm): string {
                            if (! preg_match('/^(\d{2}):(\d{2})$/', $hm, $match)) {
                                return $hm;
                            }
                            $hour = (int) $match[1];
                            $meridiem = $hour >= 12 ? 'PM' : 'AM';
                            $hour12 = $hour % 12;
                            if ($hour12 === 0) {
                                $hour12 = 12;
                            }

                            return $hour12.':'.$match[2].' '.$meridiem;
                        };
                        $spanFromEarliest = '';
                        $spanToLatest = '';
                        foreach ($rows as $spanRow) {
                            if (! is_array($spanRow)) {
                                continue;
                            }
                            $spanFrom = substr((string) ($spanRow['from_time'] ?? ''), 0, 5);
                            $spanTo = substr((string) ($spanRow['to_time'] ?? ''), 0, 5);
                            if (! preg_match('/^\d{2}:\d{2}$/', $spanFrom) || ! preg_match('/^\d{2}:\d{2}$/', $spanTo) || strcmp($spanTo, $spanFrom) <= 0) {
                                continue;
                            }
                            if ($spanFromEarliest === '' || strcmp($spanFrom, $spanFromEarliest) < 0) {
                                $spanFromEarliest = $spanFrom;
                            }
                            if ($spanToLatest === '' || strcmp($spanTo, $spanToLatest) > 0) {
                                $spanToLatest = $spanTo;
                            }
                        }
                        $spanLabel = ($spanFromEarliest !== '' && $spanToLatest !== '')
                            ? $clockFace($spanFromEarliest).'–'.$clockFace($spanToLatest)
                            : '';
                        $leaveBadge = ($card['halfLeave'] ?? false) ? 'Half day' : 'On leave';
                        $canRevokeLeave = ($card['leaveStatus'] ?? '') === 'pending' && filled($card['leaveRequestId'] ?? '');
                        $statusPills = [
                            'approved' => ['Approved', 'approved'],
                            'done' => ['Submitted', 'done'],
                            'pending' => ['Pending', 'pending'],
                            'rejected' => ['Rejected', 'rejected'],
                            'holiday' => ['Holiday', 'holiday'],
                            'off' => ['Week off', 'off'],
                            'weekoff' => ['Week off', 'off'],
                            'future' => ['Upcoming', 'future'],
                            'before' => ['Not open', 'before'],
                        ];
                        [$statusLabel, $statusClass] = $statusPills[$dayState] ?? ['Pending', 'pending'];
                    @endphp
                    <form class="ts-day {{ $startOpen ? '' : 'is-collapsed' }} is-{{ $dayState }}{{ $closed ? ' is-locked' : '' }}" id="ts-day-{{ $card['date'] }}" method="post" action="{{ route('admin.people.timesheet.day') }}" data-open="{{ $open ? '1' : '0' }}" data-date="{{ $card['date'] }}" @if($startOpen && $autoOpenDate === $card['date']) data-auto-open="1" @endif>
                        @csrf
                        <input type="hidden" name="work_date" value="{{ $card['date'] }}">
                        <input type="hidden" name="month" value="{{ $board['month'] }}">
                        <div class="ts-day-head">
                            <div class="ts-day-title">
                            <button type="button" class="ts-day-toggle" aria-expanded="{{ $startOpen ? 'true' : 'false' }}" aria-controls="ts-body-{{ $card['date'] }}" @disabled($closed)>
                                <svg class="ts-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <h3 title="{{ $card['label'] }}">{{ $shortLabel }}</h3>
                            </button>
                            <span class="ts-status-pill is-{{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($card['halfLeave'] ?? false)
                                <span class="ts-kind-pill is-half">Half day</span>
                            @elseif($card['onLeave'] ?? false)
                                <span class="ts-kind-pill is-leave">Leave</span>
                            @endif
                            </div>
                            <div class="ts-day-aside">
                            @if(! in_array($dayState, ['weekoff', 'holiday', 'future', 'off', 'before'], true))
                            <p class="ts-day-time" title="Time filled / to be filled"><b class="ts-day-total {{ $open ? 'is-open' : '' }}" data-day-total>{{ $fmt($rowHours, true) }}</b><span class="ts-day-split">/</span><b class="ts-day-target">{{ $fmt((float) ($board['minHours'] ?? 0), true) }}</b></p>
                            @endif
                            <div class="ts-day-status">
                            @if($dayState === 'holiday')
                                <span class="ts-submitted is-holiday" title="{{ $holidayLabel }}">{{ $holidayLabel }}</span>
                            @elseif($dayState === 'future')
                                <span class="ts-submitted is-future">Upcoming</span>
                            @elseif($dayState === 'off')
                                <span class="ts-submitted">Week off</span>
                            @elseif($dayState === 'before')
                                <span class="ts-submitted">Not open</span>
                            @elseif($dayState === 'weekoff')
                                <span class="ts-submitted">Week off</span>
                                <button class="ts-weekoff-undo" type="submit" name="intent" value="clear_week_off">Undo week off</button>
                            @elseif(($card['onLeave'] ?? false) && ! $open)
                                <span class="ts-submitted is-leave" title="{{ $spanLabel !== '' ? $leaveBadge.' · '.$spanLabel : $leaveBadge }}">{{ $leaveBadge }}</span>
                            @elseif($open)
                                <p class="ts-submit-hint" data-submit-hint @if($submitHint === '') hidden @endif>{{ $submitHint }}</p>
                                <button class="ts-submit" type="submit" name="intent" value="submit" @disabled($submitHint !== '')>{{ ($card['state'] ?? '') === 'done' ? 'Update' : 'Submit' }} <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                            @elseif(($card['state'] ?? '') === 'approved')
                                <span class="ts-submitted is-approved">Approved</span>
                            @elseif(($card['state'] ?? '') === 'done')
                                <span class="ts-submitted is-submitted">Submitted</span>
                            @elseif(($card['state'] ?? '') === 'rejected')
                                <span class="ts-submitted is-rejected">Rejected</span>
                            @else
                                <span class="ts-submitted is-pending">Pending</span>
                            @endif
                            </div>
                            </div>
                            @if($open || ($card['halfLeave'] ?? false))
                                <p class="ts-day-spans" data-day-spans @if($spanLabel === '') hidden @endif>{{ $spanLabel }}</p>
                            @endif
                        </div>
                        @unless($closed)
                        <div class="ts-day-body" id="ts-body-{{ $card['date'] }}" @if(! $startOpen) hidden @endif>
                        <div class="ts-day-clip">
                        <div class="ts-sheet-wrap">
                            <div class="ts-sheet ts-entries">
                                    @foreach($rows as $index => $row)
                                        @php
                                            $rowTicket = (string) ($row['ticket_id'] ?? '');
                                            $rowName = (string) ($row['task'] ?? '');
                                            $rowKind = collect($board['extras'])->contains(fn ($option) => $option['id'] === $rowTicket || ($rowTicket === '' && $rowName !== '' && $option['task'] === $rowName))
                                                ? 'extra'
                                                : 'task';
                                        @endphp
                                        @include('adminmodule::admin.people._timesheet_row', ['row' => $row, 'index' => $index, 'locked' => ! $open || $rowTicket === 'leave', 'hoursOpen' => $open && ($card['halfLeave'] ?? false) && $rowTicket === 'leave', 'kind' => $rowKind, 'assigned' => $board['assigned'], 'extras' => $board['extras'], 'leaveName' => $rowTicket === 'leave' ? ($card['leaveName'] ?? '') : '', 'leaveReason' => $rowTicket === 'leave' ? ($card['leaveReason'] ?? '') : '', 'canRevokeLeave' => $canRevokeLeave && $rowTicket === 'leave', 'leaveRevokeAction' => $canRevokeLeave && $rowTicket === 'leave' ? route('admin.people.leave.cancel', $card['leaveRequestId']) : '', 'leaveRevokeSummary' => $card['leaveRevokeSummary'] ?? ''])
                                    @endforeach
                            </div>
                        </div>
                        @if($open)
                            <div class="ts-day-add">
                                <button type="button" data-copy-previous hidden>Same as yesterday</button>
                                <button type="button" data-add="task">Add task <span>+</span></button>
                                <button type="button" data-add="extra">Add additional hours <span>+</span></button>
                                @unless($card['onLeave'] ?? false)
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#weekOffModal" data-weekoff-label="{{ $card['label'] }}">Apply week off</button>
                                @endunless
                            </div>
                        @endif
                        </div>
                        </div>
                        @endunless
                    </form>
                    @endif
                @empty
                    <p class="ts-week-empty">No days this week.</p>
                @endforelse
                </section>
                @endforeach
                </div>
                @endif
            </div>

            <footer class="ts-status">
                <div class="ts-status-top">
                    <mark>Timesheet</mark>
                    <span>Status for this month (days)</span>
                    @if($pendingCount > 0)
                        <p class="ts-alert">{{ $pendingCopy }} <button type="button" id="ts-open-pending">Click Here to view</button></p>
                    @endif
                </div>
                <ul class="ts-legend">
                    <li><i class="ts-chip is-pending"></i> Pending</li>
                    <li><i class="ts-chip is-done"></i> Submitted</li>
                    <li><i class="ts-chip is-approved"></i> Approved</li>
                    <li><i class="ts-chip is-rejected"></i> Rejected</li>
                    <li><i class="ts-chip is-half"></i> Half day</li>
                    <li><i class="ts-chip is-leave"></i> Leave</li>
                    <li><i class="ts-chip is-holiday"></i> Holiday</li>
                    <li><i class="ts-chip is-off"></i> Week off</li>
                    <li><i class="ts-chip is-future"></i> Upcoming</li>
                </ul>
                <div class="ts-status-days">
                    @php
                        $dayStatus = [
                            'approved' => 'Approved',
                            'done' => 'Submitted',
                            'pending' => 'Pending',
                            'rejected' => 'Rejected',
                            'holiday' => 'Holiday',
                            'off' => 'Week off',
                            'weekoff' => 'Week off',
                            'future' => 'Upcoming',
                        ];
                    @endphp
                    @foreach($board['statusDays'] as $day)
                        @php
                            $dayLabel = $dayStatus[$day['state']] ?? 'Day';
                            $chipState = $day['state'] === 'weekoff' ? 'off' : $day['state'];
                            $mark = trim((string) ($day['mark'] ?? ''));
                            $chipTitle = $day['n'].' · '.$dayLabel.($mark !== '' ? ' · '.$mark : '');
                        @endphp
                        <a class="ts-chip is-{{ $chipState }}" href="#ts-day-{{ $day['date'] }}" title="{{ $chipTitle }}">{{ $day['n'] }}</a>
                    @endforeach
                </div>
            </footer>
        </section>
    </div>

    <aside class="ts-pending" id="ts-pending" hidden>
        <div class="ts-pending-head">
            <h2>Pending <mark>Timesheet</mark></h2>
            <button type="button" id="ts-close-pending" aria-label="Close pending timesheet">×</button>
        </div>
        @forelse($board['pendingMonths'] as $pendingMonth)
            <h3>{{ $pendingMonth['label'] }}</h3>
            <div class="ts-pend-days">
                @foreach($pendingMonth['days'] as $day)
                    <a class="ts-chip is-{{ $day['state'] }}" href="{{ route('admin.people.index', ['section' => 'timesheet', 'month' => $pendingMonth['value']]) }}#ts-day-{{ $day['date'] }}">{{ $day['n'] }}</a>
                @endforeach
            </div>
        @empty
            <p class="ts-empty">No pending days.</p>
        @endforelse
    </aside>
</div>

<template id="ts-row-tpl">
    @include('adminmodule::admin.people._timesheet_row', ['row' => [], 'index' => '__I__', 'locked' => false, 'kind' => 'task', 'assigned' => $board['assigned'], 'extras' => $board['extras']])
</template>
<template id="ts-row-extra-tpl">
    @include('adminmodule::admin.people._timesheet_row', ['row' => [], 'index' => '__I__', 'locked' => false, 'kind' => 'extra', 'assigned' => $board['assigned'], 'extras' => $board['extras']])
</template>

<div class="modal fade" id="revokeLeaveModal" tabindex="-1" aria-labelledby="revokeLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form id="revoke-leave-form" method="post" action="">
                @csrf
                <input type="hidden" name="return_section" value="timesheet">
                <input type="hidden" name="month" value="{{ $board['month'] }}">
                <div class="modal-header">
                    <h2 class="modal-title" id="revokeLeaveModalLabel">Revoke this leave</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="people-ws-note" id="revoke-leave-summary">This request will be cancelled.</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Keep it</button>
                    <button class="btn-pw danger" type="submit">Revoke</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="weekOffModal" tabindex="-1" aria-labelledby="weekOffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <div class="modal-header">
                <h2 class="modal-title" id="weekOffModalLabel">Mark this day as a week off?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="people-ws-note" id="weekOffModalText">This day will be marked as a week off.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-pw primary" type="button" id="weekOffModalConfirm">Mark week off</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('ts-app');
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    var minHours = Number(root.getAttribute('data-min-hours')) || 0;
    var tpl = document.getElementById('ts-row-tpl');
    var extraTpl = document.getElementById('ts-row-extra-tpl');
    var query = '';
    var rowSeq = 1;

    function fmt(hours, withMinutes) {
        var total = Math.round((Number(hours) || 0) * 60);
        var h = Math.floor(total / 60);
        var m = total % 60;
        if (!withMinutes && m === 0) return h + 'h';
        return h + 'h ' + String(m).padStart(2, '0') + 'm';
    }

    function bindSelect(select) {
        if (!select || select.dataset.selectBound === '1' || !window.jQuery || !jQuery.fn.select2) return;
        select.dataset.selectBound = '1';
        jQuery(select).select2({
            width: '100%',
            dropdownParent: jQuery(document.body),
            dropdownCssClass: 'ts-task-dropdown',
            minimumResultsForSearch: 0,
            placeholder: select.getAttribute('data-placeholder') || 'Select task'
        }).on('change', function () {
            var row = select.closest('.ts-row');
            if (row) applyTask(row);
            applyFilters();
        });
    }

    function bindSelects(scope) {
        (scope || root).querySelectorAll('.ts-day .ts-task-select').forEach(bindSelect);
    }

    function setTaskValue(select, value) {
        if (!select) return;
        select.value = value || '';
        if (window.jQuery && jQuery(select).data('select2')) {
            jQuery(select).val(select.value).trigger('change');
        }
    }

    function applyTask(row) {
        var select = row.querySelector('.ts-task-select');
        var option = select && select.selectedOptions ? select.selectedOptions[0] : null;
        var title = row.querySelector('.ts-task-title');
        var due = row.querySelector('.ts-due');
        if (!option || !option.value) {
            if (title) title.value = '';
            if (due) due.value = '';
            return;
        }
        if (title) title.value = option.getAttribute('data-title') || option.textContent.trim();
        if (due) due.value = option.getAttribute('data-deadline') || '';
    }

    function clockMinutes(value) {
        var match = /^(\d{2}):(\d{2})/.exec(value || '');
        if (!match) return null;
        var hours = Number(match[1]);
        var minutes = Number(match[2]);
        if (hours > 23 || minutes > 59) return null;
        return hours * 60 + minutes;
    }

    function clockFace(value) {
        var total = clockMinutes(value);
        if (total === null) return '';
        var hour24 = Math.floor(total / 60);
        var minute = total % 60;
        var hour12 = hour24 % 12 || 12;
        return hour12 + ':' + String(minute).padStart(2, '0') + ' ' + (hour24 >= 12 ? 'PM' : 'AM');
    }

    function daySpanLabel(day) {
        var earliest = null;
        var latest = null;
        var fromValue = '';
        var toValue = '';
        day.querySelectorAll('.ts-row').forEach(function (row) {
            var from = row.querySelector('.ts-from');
            var to = row.querySelector('.ts-to');
            if (!from || !to) return;
            var start = clockMinutes(from.value);
            var end = clockMinutes(to.value);
            if (start === null || end === null || end <= start) return;
            if (earliest === null || start < earliest) {
                earliest = start;
                fromValue = from.value;
            }
            if (latest === null || end > latest) {
                latest = end;
                toValue = to.value;
            }
        });
        if (earliest === null || latest === null) return '';
        return clockFace(fromValue) + '–' + clockFace(toValue);
    }

    function applySpan(row) {
        var from = row.querySelector('.ts-from');
        var to = row.querySelector('.ts-to');
        var hours = row.querySelector('.ts-hours');
        var view = row.querySelector('.ts-hours-view');
        if (!from || !to || !hours || hours.getAttribute('data-manual') === '1') return;
        var start = clockMinutes(from.value);
        var end = clockMinutes(to.value);
        var amount = (start === null || end === null || end <= start) ? 0 : Math.round(((end - start) / 60) * 100) / 100;
        hours.value = amount > 0 ? String(amount) : '';
        if (view) view.value = amount > 0 ? fmt(amount, true) : '';
        if (to) to.title = (start !== null && end !== null && end <= start) ? 'To time has to be later than from time.' : '';
    }

    function dayTotals(day) {
        var count = 0;
        var hours = 0;
        day.querySelectorAll('.ts-row').forEach(function (row) {
            var task = row.querySelector('.ts-task-select');
            var hourInput = row.querySelector('.ts-hours');
            var amount = hourInput ? Number(hourInput.value) || 0 : 0;
            hours += amount;
            if (task && task.value && amount > 0) count += 1;
        });
        return { count: count, hours: hours };
    }

    function dayCanSubmit(day) {
        return submitHint(day) === '';
    }

    function submitHint(day) {
        var totals = dayTotals(day);
        var least = fmt(minHours, false);
        if (totals.count < 1 || totals.hours <= 0) {
            return minHours > 0 ? ('Add a task and at least ' + least + '.') : 'Add a task with hours.';
        }
        if (totals.hours > 24) return 'A day cannot be more than 24h.';
        if (minHours > 0 && totals.hours + 0.001 < minHours) return 'A day needs at least ' + least + '.';
        return '';
    }

    function rowKind(row) {
        var select = row.querySelector('.ts-task-select');
        return select && select.getAttribute('data-placeholder') === 'Select additional hours' ? 'extra' : 'task';
    }

    function rowSnapshot(row) {
        var select = row.querySelector('.ts-task-select');
        var hours = row.querySelector('.ts-hours');
        var from = row.querySelector('.ts-from');
        var to = row.querySelector('.ts-to');
        var desc = row.querySelector('.ts-desc');
        var title = row.querySelector('.ts-task-title');
        var ticket = select ? select.value : '';
        if (ticket === 'leave' || ticket === 'partial-leave') ticket = '';
        return {
            kind: rowKind(row),
            ticket: ticket,
            label: title ? title.value : '',
            hours: hours ? hours.value : '',
            from: from ? from.value : '',
            to: to ? to.value : '',
            description: desc ? desc.value : ''
        };
    }

    function dayIsBlank(day) {
        var rows = day.querySelectorAll('.ts-row');
        if (!rows.length) return true;
        for (var i = 0; i < rows.length; i++) {
            var snap = rowSnapshot(rows[i]);
            if (snap.ticket || snap.description || snap.from || snap.to || (Number(snap.hours) || 0) > 0) return false;
        }
        return true;
    }

    function previousWork(day) {
        var days = Array.prototype.slice.call(root.querySelectorAll('.ts-day'));
        var index = days.indexOf(day);
        for (var i = index - 1; i >= 0; i--) {
            var candidate = days[i];
            if (candidate.classList.contains('is-locked') || candidate.classList.contains('is-holiday') || candidate.classList.contains('is-future') || candidate.classList.contains('is-off') || candidate.classList.contains('is-weekoff')) continue;
            var snaps = [];
            candidate.querySelectorAll('.ts-row').forEach(function (row) {
                var snap = rowSnapshot(row);
                if (!snap.ticket) return;
                if ((Number(snap.hours) || 0) <= 0 && !snap.from && !snap.to) return;
                snaps.push(snap);
            });
            if (snaps.length) return { snaps: snaps, date: candidate.getAttribute('data-date') || '' };
        }
        return { snaps: [], date: '' };
    }

    function copyLabel(target, sourceDate) {
        if (!target || !sourceDate) return 'Same as yesterday';
        var targetDate = target.getAttribute('data-date') || '';
        var previous = new Date(targetDate + 'T00:00:00');
        previous.setDate(previous.getDate() - 1);
        var key = previous.getFullYear() + '-' + String(previous.getMonth() + 1).padStart(2, '0') + '-' + String(previous.getDate()).padStart(2, '0');
        return key === sourceDate ? 'Same as yesterday' : 'Same as last working day';
    }

    function refreshCopy(day) {
        var button = day.querySelector('[data-copy-previous]');
        if (!button) return;
        var previous = day.getAttribute('data-open') === '1' && dayIsBlank(day) ? previousWork(day) : { snaps: [], date: '' };
        if (!previous.snaps.length) {
            button.hidden = true;
            return;
        }
        button.textContent = copyLabel(day, previous.date);
        button.hidden = false;
    }

    function rowTicket(row) {
        var task = row.querySelector('.ts-task-select');
        return task ? task.value : '';
    }

    function refresh() {
        var filled = 0;
        var leave = 0;
        root.querySelectorAll('.ts-day').forEach(function (day) {
            var totals = dayTotals(day);
            var hours = totals.hours;
            if (day.getAttribute('data-outside') !== '1') {
                day.querySelectorAll('.ts-row').forEach(function (row) {
                    var hourInput = row.querySelector('.ts-hours');
                    var amount = hourInput ? Number(hourInput.value) || 0 : 0;
                    var ticket = rowTicket(row);
                    if (ticket === 'leave' || ticket === 'partial-leave') leave += amount;
                    else filled += amount;
                });
            }
            var totalNode = day.querySelector('[data-day-total]');
            var submit = day.querySelector('.ts-submit');
            var short = minHours > 0 && hours + 0.001 < minHours;
            if (totalNode) {
                totalNode.textContent = fmt(hours, true);
                totalNode.classList.toggle('is-short', day.getAttribute('data-open') === '1' && short);
            }
            if (submit) {
                var hint = submitHint(day);
                submit.disabled = hint !== '';
                submit.title = hint;
                var hintNode = day.querySelector('[data-submit-hint]');
                if (hintNode) {
                    hintNode.textContent = hint;
                    hintNode.hidden = hint === '';
                }
            }
            refreshCopy(day);
            var spans = day.querySelector('[data-day-spans]');
            if (spans) {
                var spanLabel = daySpanLabel(day);
                spans.textContent = spanLabel;
                spans.hidden = spanLabel === '';
            }
        });
        var filledNode = document.getElementById('ts-filled');
        var leaveNode = document.getElementById('ts-leave-hours');
        if (filledNode) filledNode.textContent = fmt(filled, false);
        if (leaveNode) leaveNode.textContent = fmt(leave, false);
    }

    function visible(row) {
        var task = row.querySelector('.ts-task-select');
        var option = task && task.selectedOptions ? task.selectedOptions[0] : null;
        var text = option ? option.textContent : '';
        return !query || text.toLowerCase().indexOf(query) !== -1;
    }

    var weekBar = root.querySelector('.ts-week-bar');
    var weekPage = weekBar ? Number(weekBar.getAttribute('data-week-page')) || 0 : 0;
    var weekCount = weekBar ? Number(weekBar.getAttribute('data-week-count')) || 1 : 1;
    var weekSpan = 1;
    var boardMotion = 0;
    var boardReady = false;
    var motionEase = 'cubic-bezier(0.4, 0, 0.2, 1)';

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function placeViewPill() {
        var view = root.querySelector('.ts-view');
        if (!view) return;
        var on = view.querySelector('button.is-on');
        if (!on) return;
        view.style.setProperty('--ts-pill-x', on.offsetLeft + 'px');
        view.style.setProperty('--ts-pill-w', on.offsetWidth + 'px');
        view.classList.add('is-ready');
    }

    function formatRange(start, end) {
        if (!start || !end) return '';
        var a = new Date(start + 'T00:00:00');
        var b = new Date(end + 'T00:00:00');
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        if (start === end) return a.getDate() + ' ' + months[a.getMonth()];
        if (a.getMonth() === b.getMonth() && a.getFullYear() === b.getFullYear()) {
            return a.getDate() + '–' + b.getDate() + ' ' + months[b.getMonth()];
        }
        if (a.getFullYear() === b.getFullYear()) {
            return a.getDate() + ' ' + months[a.getMonth()] + '–' + b.getDate() + ' ' + months[b.getMonth()];
        }
        return a.getDate() + ' ' + months[a.getMonth()] + ' ' + a.getFullYear() + '–' + b.getDate() + ' ' + months[b.getMonth()] + ' ' + b.getFullYear();
    }

    function pageCount() {
        if (weekSpan === 0) return 1;
        return Math.max(1, Math.ceil(weekCount / weekSpan));
    }

    function pageForWeek(number) {
        if (weekSpan === 0) return 0;
        return Math.floor((number - 1) / weekSpan);
    }

    function pageForDay(day) {
        var week = day && day.closest('.ts-week');
        return week ? pageForWeek(Number(week.getAttribute('data-week'))) : weekPage;
    }

    function paintWeekPage() {
        var pages = pageCount();
        var page = weekPage < 0 ? 0 : (weekPage > pages - 1 ? pages - 1 : weekPage);
        weekPage = page;
        if (weekBar) weekBar.setAttribute('data-week-page', String(page));
        var visible = [];
        root.querySelectorAll('.ts-week').forEach(function (week) {
            var on = weekSpan === 0 || pageForWeek(Number(week.getAttribute('data-week'))) === page;
            week.classList.toggle('is-off', !on);
            if (on) visible.push(week);
        });
        var label = root.querySelector('[data-week-label]');
        if (label && visible.length) {
            label.textContent = formatRange(visible[0].getAttribute('data-start'), visible[visible.length - 1].getAttribute('data-end'));
        }
        var weeksEl = root.querySelector('.ts-weeks');
        if (weeksEl) {
            weeksEl.classList.toggle('is-one', weekSpan === 1);
            weeksEl.classList.toggle('is-month', weekSpan === 0);
            weeksEl.style.setProperty('--ts-week-cols', String(Math.max(visible.length, 1)));
        }
        root.querySelectorAll('[data-week-step]').forEach(function (button) {
            var step = Number(button.getAttribute('data-week-step'));
            button.disabled = weekSpan === 0 || (step < 0 && page === 0) || (step > 0 && page >= pages - 1);
        });
        writePlace();
    }

    function readPlace() {
        try {
            var raw = sessionStorage.getItem('people-timesheet-place');
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function currentMonthValue() {
        var monthSelect = document.getElementById('ts-month');
        return monthSelect ? monthSelect.value : '';
    }

    function writePlace() {
        var days = document.getElementById('ts-days');
        var shown = root.querySelector('.ts-week:not(.is-off)');
        try {
            sessionStorage.setItem('people-timesheet-place', JSON.stringify({
                month: currentMonthValue(),
                span: weekSpan,
                start: shown ? shown.getAttribute('data-start') : '',
                left: days ? days.scrollLeft : 0,
                top: days ? days.scrollTop : 0
            }));
        } catch (e) {}
    }

    function restorePlace() {
        var saved = readPlace();
        if (!saved) return null;
        var span = Number(saved.span);
        if (span !== 0 && span !== 1 && span !== 2) return null;
        weekSpan = span;
        root.querySelectorAll('[data-view]').forEach(function (button) {
            button.classList.toggle('is-on', Number(button.getAttribute('data-view')) === weekSpan);
        });
        var serverWeek = (Number(weekBar && weekBar.getAttribute('data-week-page')) || 0) + 1;
        weekPage = weekSpan === 0 ? 0 : Math.floor((serverWeek - 1) / weekSpan);
        if (saved.month === currentMonthValue() && /^\d{4}-\d{2}-\d{2}$/.test(String(saved.start || ''))) {
            var week = root.querySelector('.ts-week[data-start="' + saved.start + '"]');
            if (week) weekPage = pageForWeek(Number(week.getAttribute('data-week')));
        }
        paintWeekPage();
        return saved;
    }

    function runBoardMotion(apply) {
        var weeksEl = root.querySelector('.ts-weeks');
        if (!weeksEl || !boardReady || prefersReducedMotion()) {
            apply();
            return;
        }
        if (typeof document.startViewTransition === 'function') {
            var pending = ++boardMotion;
            if (root._tsView) {
                root._tsView.skipTransition();
                root._tsView = null;
            }
            root._tsView = document.startViewTransition(function () {
                if (pending !== boardMotion) return;
                apply();
            });
            root._tsView.finished.finally(function () {
                if (root._tsView && pending === boardMotion) root._tsView = null;
            });
            return;
        }
        var gen = ++boardMotion;
        weeksEl.getAnimations().forEach(function (anim) { anim.cancel(); });
        var from = weeksEl.getBoundingClientRect().height;
        var out = weeksEl.animate(
            [{ opacity: 1, transform: 'translateY(0px)' }, { opacity: 0, transform: 'translateY(6px)' }],
            { duration: 120, easing: 'cubic-bezier(.4, 0, 1, 1)', fill: 'forwards' }
        );
        out.onfinish = function () {
            if (gen !== boardMotion) return;
            apply();
            var to = weeksEl.scrollHeight;
            weeksEl.style.overflow = 'hidden';
            var into = weeksEl.animate(
                [
                    { height: from + 'px', opacity: 0, transform: 'translateY(-6px)' },
                    { height: to + 'px', opacity: 1, transform: 'translateY(0px)' }
                ],
                { duration: 280, easing: motionEase, fill: 'forwards' }
            );
            out.cancel();
            into.onfinish = function () {
                if (gen !== boardMotion) return;
                weeksEl.style.overflow = '';
                into.cancel();
            };
        };
    }

    function showWeekPage(page) {
        var pages = pageCount();
        page = page < 0 ? 0 : (page > pages - 1 ? pages - 1 : page);
        if (page === weekPage && boardReady) {
            var weeksEl = root.querySelector('.ts-weeks');
            var already = weeksEl && ((weekSpan === 0 && weeksEl.classList.contains('is-month')) || (weekSpan === 1 && weeksEl.classList.contains('is-one')) || (weekSpan === 2 && !weeksEl.classList.contains('is-one') && !weeksEl.classList.contains('is-month')));
            if (already) return;
        }
        weekPage = page;
        runBoardMotion(paintWeekPage);
    }

    function setWeekSpan(next) {
        next = Number(next);
        if (next !== 0 && next !== 1 && next !== 2) return;
        if (next === weekSpan) return;
        var anchor = 1;
        var shown = root.querySelector('.ts-week:not(.is-off)');
        if (shown) anchor = Number(shown.getAttribute('data-week')) || 1;
        var today = weekBar ? weekBar.getAttribute('data-today') : '';
        if (today) {
            var todayDay = root.querySelector('.ts-day[data-date="' + today + '"]');
            var todayWeek = todayDay && todayDay.closest('.ts-week');
            if (todayWeek && !todayWeek.classList.contains('is-off')) anchor = Number(todayWeek.getAttribute('data-week')) || anchor;
        }
        weekSpan = next;
        root.querySelectorAll('[data-view]').forEach(function (button) {
            button.classList.toggle('is-on', Number(button.getAttribute('data-view')) === weekSpan);
        });
        placeViewPill();
        showWeekPage(weekSpan === 0 ? 0 : Math.floor((anchor - 1) / weekSpan));
    }

    function applyFilters() {
        var match = null;
        root.querySelectorAll('.ts-day').forEach(function (day) {
            var any = false;
            day.querySelectorAll('.ts-row').forEach(function (row) {
                var show = visible(row);
                row.hidden = !show;
                if (show) any = true;
            });
            day.classList.toggle('is-filtered-out', !any && query !== '');
            if (query !== '' && any && !match) match = day;
            if (query !== '') setDayOpen(day, any);
        });
        if (match) showWeekPage(pageForDay(match));
        refresh();
    }

    function setDayOpen(day, open) {
        if (!day || day.classList.contains('is-locked')) return;
        var body = day.querySelector('.ts-day-body');
        var clip = body ? (body.querySelector('.ts-day-clip') || body) : null;
        var toggle = day.querySelector('.ts-day-toggle');
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (!body || !clip || prefersReducedMotion()) {
            day.classList.toggle('is-collapsed', !open);
            if (body) {
                if (open) body.removeAttribute('hidden');
                else body.setAttribute('hidden', '');
            }
            if (clip) clip.style.height = '';
            if (open) bindSelects(day);
            return;
        }

        if (open && !day.classList.contains('is-collapsed') && !body.hasAttribute('hidden')) {
            bindSelects(day);
            return;
        }
        if (!open && day.classList.contains('is-collapsed') && body.hasAttribute('hidden')) return;

        var current = clip.getBoundingClientRect().height;
        if (clip._tsAnim) {
            clip._tsAnim.cancel();
            clip._tsAnim = null;
        }

        if (open) {
            body.removeAttribute('hidden');
            day.classList.remove('is-collapsed');
            bindSelects(day);
            var target = clip.scrollHeight;
            var anim = clip.animate(
                [{ height: current + 'px' }, { height: target + 'px' }],
                { duration: 300, easing: motionEase, fill: 'forwards' }
            );
            clip._tsAnim = anim;
            anim.onfinish = function () {
                if (clip._tsAnim !== anim) return;
                clip._tsAnim = null;
                clip.style.height = '';
                anim.cancel();
            };
            anim.oncancel = function () {
                if (clip._tsAnim === anim) clip._tsAnim = null;
            };
            return;
        }

        day.classList.add('is-collapsed');
        var animClose = clip.animate(
            [{ height: current + 'px' }, { height: '0px' }],
            { duration: 300, easing: motionEase, fill: 'forwards' }
        );
        clip._tsAnim = animClose;
        animClose.onfinish = function () {
            if (clip._tsAnim !== animClose) return;
            clip._tsAnim = null;
            if (day.classList.contains('is-collapsed')) body.setAttribute('hidden', '');
            clip.style.height = '';
            animClose.cancel();
        };
        animClose.oncancel = function () {
            if (clip._tsAnim === animClose) clip._tsAnim = null;
        };
    }

    function openFromHash() {
        var id = (window.location.hash || '').replace('#', '');
        if (!id) return;
        var day = document.getElementById(id);
        if (!day || !day.classList.contains('ts-day')) return;
        showWeekPage(pageForDay(day));
        setDayOpen(day, true);
        day.scrollIntoView({ block: 'nearest' });
    }

    function addRow(day, preset) {
        var body = day.querySelector('.ts-entries');
        var source = preset && preset.kind === 'extra' ? extraTpl : tpl;
        if (!body || !source) return;
        var html = source.innerHTML.split('__I__').join('n' + (rowSeq++));
        var holder = document.createElement('div');
        holder.innerHTML = html.trim();
        var row = holder.querySelector('.ts-row');
        if (!row) return;
        body.appendChild(row);
        if (window.peopleTimePaint) window.peopleTimePaint(row);
        var taskSelect = row.querySelector('.ts-task-select');
        if (preset && preset.ticket && taskSelect && !taskSelect.querySelector('option[value="' + preset.ticket + '"]')) {
            var extra = document.createElement('option');
            extra.value = preset.ticket;
            extra.textContent = preset.label || preset.ticket;
            extra.setAttribute('data-title', extra.textContent);
            extra.setAttribute('data-deadline', '');
            taskSelect.appendChild(extra);
        }
        bindSelect(taskSelect);
        if (preset) {
            var from = row.querySelector('.ts-from');
            var to = row.querySelector('.ts-to');
            var desc = row.querySelector('.ts-desc');
            var hours = row.querySelector('.ts-hours');
            if (from && preset.from) from.value = preset.from;
            if (to && preset.to) to.value = preset.to;
            if (desc && preset.description) desc.value = preset.description;
            if (hours && preset.hours) hours.value = String(preset.hours);
            if (taskSelect && preset.ticket) setTaskValue(taskSelect, preset.ticket);
            else applyTask(row);
            applySpan(row);
            if (window.peopleTimePaint) window.peopleTimePaint(row);
        }
        applyFilters();
    }

    root.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.classList || !form.classList.contains('ts-day')) return;
        var submitter = event.submitter;
        if (submitter && submitter.name === 'intent' && submitter.value) {
            var intent = form.querySelector('input[data-ts-intent]');
            if (!intent) {
                intent = document.createElement('input');
                intent.type = 'hidden';
                intent.name = 'intent';
                intent.setAttribute('data-ts-intent', '1');
                form.appendChild(intent);
            }
            intent.value = submitter.value;
        }
    }, true);

    root.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.classList || !form.classList.contains('ts-day') || form.getAttribute('data-open') !== '1') return;
        var submitter = event.submitter;
        if (submitter && submitter.name === 'intent' && submitter.value !== 'submit') return;
        if (dayCanSubmit(form)) return;
        event.preventDefault();
        var totals = dayTotals(form);
        if (minHours > 0 && totals.hours + 0.001 < minHours && window.toastr) {
            toastr.error('A day needs at least ' + String(minHours) + ' hours.');
        }
    });

    var revokeModal = document.getElementById('revokeLeaveModal');
    if (revokeModal) {
        revokeModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var form = document.getElementById('revoke-leave-form');
            var summary = document.getElementById('revoke-leave-summary');
            if (!button || !form) return;
            form.action = button.getAttribute('data-action') || '';
            if (summary) summary.textContent = button.getAttribute('data-summary') || 'This request will be cancelled.';
        });
    }

    var weekOffForm = null;
    var weekOffModal = document.getElementById('weekOffModal');
    if (weekOffModal) {
        weekOffModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            weekOffForm = button ? button.closest('.ts-day') : null;
            var text = document.getElementById('weekOffModalText');
            var label = button ? (button.getAttribute('data-weekoff-label') || '') : '';
            var hours = weekOffForm ? dayTotals(weekOffForm).hours : 0;
            var warning = hours > 0 ? 'Hours already entered for this day will be cleared.' : '';
            if (text) {
                if (label !== '' && warning !== '') text.textContent = label + '. ' + warning;
                else if (label !== '') text.textContent = label + '.';
                else if (warning !== '') text.textContent = warning;
                else text.textContent = 'This day will be marked as a week off.';
            }
        });
        var confirmWeekOff = document.getElementById('weekOffModalConfirm');
        if (confirmWeekOff) {
            confirmWeekOff.addEventListener('click', function () {
                var form = weekOffForm;
                if (!form) return;
                var submitter = form.querySelector('[data-weekoff-submit]');
                if (!submitter) {
                    submitter = document.createElement('button');
                    submitter.type = 'submit';
                    submitter.name = 'intent';
                    submitter.value = 'week_off';
                    submitter.hidden = true;
                    submitter.setAttribute('data-weekoff-submit', '1');
                    form.appendChild(submitter);
                }
                if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(weekOffModal).hide();
                form.requestSubmit(submitter);
            });
        }
    }

    root.addEventListener('change', function (event) {
        var row = event.target.closest('.ts-row');
        if (event.target.matches('.ts-task-select') && row) applyTask(row);
        if (event.target.matches('.ts-from, .ts-to') && row) applySpan(row);
        if (event.target.matches('.ts-task-select, .ts-hours, .ts-from, .ts-to')) applyFilters();
    });

    root.addEventListener('input', function (event) {
        var row = event.target.closest('.ts-row');
        if (event.target.matches('.ts-from, .ts-to') && row) applySpan(row);
        if (event.target.matches('.ts-hours, .ts-from, .ts-to')) refresh();
    });

    root.addEventListener('click', function (event) {
        var chip = event.target.closest('a.ts-chip[href^="#ts-day-"]');
        if (chip) {
            var linked = document.getElementById((chip.getAttribute('href') || '').slice(1));
            if (linked) {
                showWeekPage(pageForDay(linked));
                linked.scrollIntoView({ block: 'nearest' });
                setDayOpen(linked, true);
            }
        }
        var toggle = event.target.closest('.ts-day-toggle');
        if (toggle) {
            var toggled = toggle.closest('.ts-day');
            if (toggled) setDayOpen(toggled, toggled.classList.contains('is-collapsed'));
            return;
        }
        var head = event.target.closest('.ts-day-head');
        if (head && !event.target.closest('button, a, input, select, textarea, label')) {
            var headed = head.closest('.ts-day');
            if (headed) setDayOpen(headed, headed.classList.contains('is-collapsed'));
            return;
        }
        var viewButton = event.target.closest('[data-view]');
        if (viewButton) {
            setWeekSpan(viewButton.getAttribute('data-view'));
            return;
        }
        var weekStep = event.target.closest('[data-week-step]');
        if (weekStep && !weekStep.disabled) {
            showWeekPage(weekPage + Number(weekStep.getAttribute('data-week-step')));
            return;
        }
        var copyPrevious = event.target.closest('[data-copy-previous]');
        if (copyPrevious) {
            var copyDay = copyPrevious.closest('.ts-day');
            var previous = copyDay ? previousWork(copyDay) : { snaps: [] };
            var copyBody = copyDay ? copyDay.querySelector('.ts-entries') : null;
            if (!copyDay || !copyBody || !previous.snaps.length || !dayIsBlank(copyDay)) return;
            Array.prototype.slice.call(copyBody.querySelectorAll('.ts-row')).forEach(function (row) {
                var select = row.querySelector('.ts-task-select');
                if (select && window.jQuery && jQuery(select).data('select2')) jQuery(select).select2('destroy');
                row.remove();
            });
            previous.snaps.forEach(function (snap) { addRow(copyDay, snap); });
            refresh();
            return;
        }
        var add = event.target.closest('[data-add]');
        if (add) {
            var day = add.closest('.ts-day');
            if (!day) return;
            var addKind = add.getAttribute('data-add');
            if (addKind === 'extra') addRow(day, { kind: 'extra' });
            else addRow(day);
            return;
        }
        var remove = event.target.closest('.ts-remove');
        if (remove) {
            var row = remove.closest('.ts-row');
            var body = row && row.parentElement;
            if (!row || !body) return;
            if (body.querySelectorAll('.ts-row').length < 2) {
                var task = row.querySelector('.ts-task-select');
                var hours = row.querySelector('.ts-hours');
                var desc = row.querySelector('.ts-desc');
                var code = row.querySelector('.ts-code');
                var type = row.querySelector('.ts-type');
                if (task) setTaskValue(task, '');
            if (hours) hours.value = '';
            var from = row.querySelector('.ts-from');
            var to = row.querySelector('.ts-to');
            var view = row.querySelector('.ts-hours-view');
            if (from) from.value = '';
            if (to) { to.value = ''; to.title = ''; }
            if (view) view.value = '';
            if (window.peopleTimePaint) window.peopleTimePaint(row);
            if (desc) desc.value = '';
                if (code) code.value = '';
                if (type) type.value = '';
                applyTask(row);
            } else {
                row.remove();
            }
            applyFilters();
            return;
        }
        var copy = event.target.closest('.ts-copy');
        if (copy) {
            var codeInput = copy.closest('.ts-row').querySelector('.ts-code');
            var value = codeInput ? codeInput.value : '';
            if (!value) return;
            if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(value);
            copy.setAttribute('data-copied', '1');
            setTimeout(function () { copy.removeAttribute('data-copied'); }, 1200);
        }
    });

    var month = document.getElementById('ts-month');
    if (month) {
        month.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('section', 'timesheet');
            url.searchParams.set('month', month.value);
            url.hash = '';
            window.location = url.toString();
        });
    }

    var search = document.getElementById('ts-search');
    if (search) search.addEventListener('input', function () { query = search.value.trim().toLowerCase(); applyFilters(); });

    var pending = document.getElementById('ts-pending');
    var openPending = document.getElementById('ts-open-pending');
    var closePending = document.getElementById('ts-close-pending');
    if (openPending && pending) openPending.addEventListener('click', function () { pending.removeAttribute('hidden'); });
    if (closePending && pending) closePending.addEventListener('click', function () { pending.setAttribute('hidden', ''); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && pending) pending.setAttribute('hidden', '');
    });

    var savedPlace = restorePlace();
    applyFilters();
    root.querySelectorAll('.ts-day:not(.is-collapsed)').forEach(function (day) { bindSelects(day); });
    openFromHash();
    var daysBoard = document.getElementById('ts-days');
    if (savedPlace && savedPlace.month === currentMonthValue() && !(window.location.hash || '').replace('#', '') && daysBoard) {
        daysBoard.scrollLeft = Number(savedPlace.left) || 0;
        daysBoard.scrollTop = Number(savedPlace.top) || 0;
        writePlace();
    } else if (!(window.location.hash || '').replace('#', '')) {
        var openedDay = root.querySelector('.ts-day[data-auto-open="1"]');
        if (openedDay) openedDay.scrollIntoView({ block: 'nearest' });
    }
    if (daysBoard) {
        var placeScrollTimer;
        daysBoard.addEventListener('scroll', function () {
            clearTimeout(placeScrollTimer);
            placeScrollTimer = setTimeout(writePlace, 80);
        }, { passive: true });
    }
    boardReady = true;
    placeViewPill();
    window.addEventListener('resize', placeViewPill);
    window.addEventListener('hashchange', openFromHash);
})();
</script>
