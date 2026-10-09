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
@endphp
<div class="ts-app" id="ts-app" data-min-hours="{{ $board['minHours'] ?? 0 }}">
    <div class="ts-main">
        @if($pendingCount > 0)
            <p class="ts-alert">{{ $pendingCopy }} <button type="button" id="ts-open-pending">Click Here to view</button></p>
        @endif
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
                <article class="ts-stat ts-stat--total ts-head-hours">
                    <strong id="ts-total">{{ $fmt($board['total']) }}</strong>
                    <span>Total Hours</span>
                </article>
                <button class="ts-leave-btn" type="button" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">I was on Leave</button>
                <div class="ts-tools">
                    <label class="ts-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 16l4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <input id="ts-search" type="search" placeholder="Search" aria-label="Search tasks and additional hours">
                    </label>
                </div>
            </header>
            @if(($board['minHours'] ?? 0) > 0)
                <p class="ts-rule">Fill at least {{ \Modules\AdminModule\Services\PeopleWorkspace::hoursText((float) $board['minHours']) }} hours on a working day before you submit it.</p>
            @endif

            <div class="ts-days" id="ts-days">
                @forelse($board['cards'] as $card)
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
                    @endphp
                    <form class="ts-day {{ $useOld ? '' : 'is-collapsed' }} is-{{ in_array($card['state'] ?? '', ['pending', 'done', 'approved', 'leave', 'weekoff'], true) ? $card['state'] : 'pending' }}" id="ts-day-{{ $card['date'] }}" method="post" action="{{ route('admin.people.timesheet.day') }}" data-open="{{ $open ? '1' : '0' }}" data-date="{{ $card['date'] }}">
                        @csrf
                        <input type="hidden" name="work_date" value="{{ $card['date'] }}">
                        <input type="hidden" name="month" value="{{ $board['month'] }}">
                        <div class="ts-day-head">
                            <button type="button" class="ts-day-toggle" aria-expanded="{{ $useOld ? 'true' : 'false' }}" aria-controls="ts-body-{{ $card['date'] }}">
                                <svg class="ts-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <h3>{{ $card['label'] }}</h3>
                            </button>
                            <div class="ts-day-aside">
                            @if(($card['state'] ?? '') !== 'weekoff')
                            <p class="ts-day-time" title="Time filled / to be filled"><b class="ts-day-total {{ $open ? 'is-open' : '' }}" data-day-total>{{ $fmt($rowHours, true) }}</b><span class="ts-day-split">/</span><b class="ts-day-target">{{ $fmt((float) ($board['minHours'] ?? 0), true) }}</b></p>
                            @endif
                            @if(($card['state'] ?? '') === 'weekoff')
                                <span class="ts-submitted">Week off</span>
                                <button class="ts-weekoff-undo" type="submit" name="intent" value="clear_week_off">Undo week off</button>
                            @elseif(($card['onLeave'] ?? false) && ! $open)
                                <span class="ts-submitted is-leave">{{ ($card['halfLeave'] ?? false) ? 'Half day · on leave' : 'On leave' }}</span>
                            @elseif($open)
                                <button class="ts-submit" type="submit" name="intent" value="submit" @disabled($taskCount < 1 || (($board['minHours'] ?? 0) > 0 && $rowHours + 0.001 < (float) $board['minHours']))>{{ ($card['state'] ?? '') === 'done' ? 'Update' : 'Submit' }} <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                            @elseif(($card['state'] ?? '') === 'approved')
                                <span class="ts-submitted is-approved">Approved</span>
                            @elseif(($card['state'] ?? '') === 'done')
                                <span class="ts-submitted is-submitted">Submitted</span>
                            @else
                                <span class="ts-submitted">With manager</span>
                            @endif
                            </div>
                        </div>
                        <div class="ts-day-body" id="ts-body-{{ $card['date'] }}" @if(! $useOld) hidden @endif>
                        <div class="ts-sheet-wrap">
                            <table class="ts-sheet">
                                <thead>
                                    <tr>
                                        <th>Task</th>
                                        <th>Hours</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $index => $row)
                                        @php
                                            $rowTicket = (string) ($row['ticket_id'] ?? '');
                                            $rowName = (string) ($row['task'] ?? '');
                                            $rowKind = collect($board['extras'])->contains(fn ($option) => $option['id'] === $rowTicket || ($rowTicket === '' && $rowName !== '' && $option['task'] === $rowName))
                                                ? 'extra'
                                                : 'task';
                                        @endphp
                                        @include('adminmodule::admin.people._timesheet_row', ['row' => $row, 'index' => $index, 'locked' => ! $open || $rowTicket === 'leave', 'hoursOpen' => $open && ($card['halfLeave'] ?? false) && $rowTicket === 'leave', 'kind' => $rowKind, 'assigned' => $board['assigned'], 'extras' => $board['extras'], 'leaveName' => $rowTicket === 'leave' ? ($card['leaveName'] ?? '') : '', 'leaveReason' => $rowTicket === 'leave' ? ($card['leaveReason'] ?? '') : ''])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($open)
                            <div class="ts-day-add">
                                <button type="button" data-add="task">Add task <span>+</span></button>
                                <button type="button" data-add="extra">Add additional hours <span>+</span></button>
                                @unless($card['onLeave'] ?? false)
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#weekOffModal" data-weekoff-label="{{ $card['label'] }}">Week off</button>
                                @endunless
                            </div>
                        @endif
                        </div>
                    </form>
                @empty
                    <p class="ts-empty">No working days to fill in {{ $board['monthLabel'] }}.</p>
                @endforelse
            </div>

            <footer class="ts-status">
                <mark>Timesheet</mark>
                <span>Status for this month (days)</span>
                <ul class="ts-legend">
                    <li><i class="ts-chip is-approved"></i> Approved</li>
                    <li><i class="ts-chip is-done"></i> Submitted</li>
                    <li><i class="ts-chip is-pending"></i> Unfilled</li>
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
                            'pending' => 'Unfilled',
                            'leave' => 'Leave',
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
                        @endphp
                        @if(in_array($day['state'], ['pending', 'done', 'approved', 'leave', 'weekoff'], true))
                            <a class="ts-chip is-{{ $chipState }}" href="#ts-day-{{ $day['date'] }}" title="{{ $day['n'] }} · {{ $dayLabel }}">{{ $day['n'] }}</a>
                        @else
                            <span class="ts-chip is-{{ $day['state'] }}" title="{{ $day['n'] }} · {{ $dayLabel }}">{{ $day['n'] }}</span>
                        @endif
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
        var totals = dayTotals(day);
        if (totals.count < 1 || totals.hours <= 0 || totals.hours > 24) return false;
        if (minHours > 0 && totals.hours + 0.001 < minHours) return false;
        return true;
    }

    function refresh() {
        var total = 0;
        root.querySelectorAll('.ts-day').forEach(function (day) {
            var totals = dayTotals(day);
            var hours = totals.hours;
            total += hours;
            var totalNode = day.querySelector('[data-day-total]');
            var submit = day.querySelector('.ts-submit');
            var short = minHours > 0 && hours + 0.001 < minHours;
            if (totalNode) {
                totalNode.textContent = fmt(hours, true);
                totalNode.classList.toggle('is-short', day.getAttribute('data-open') === '1' && short);
            }
            if (submit) {
                submit.disabled = !dayCanSubmit(day);
                submit.title = short ? ('A day needs at least ' + String(minHours) + ' hours.') : '';
            }
        });
        var totalNode = document.getElementById('ts-total');
        if (totalNode) totalNode.textContent = fmt(total, false);
    }

    function visible(row) {
        var task = row.querySelector('.ts-task-select');
        var option = task && task.selectedOptions ? task.selectedOptions[0] : null;
        var text = option ? option.textContent : '';
        return !query || text.toLowerCase().indexOf(query) !== -1;
    }

    function applyFilters() {
        root.querySelectorAll('.ts-day').forEach(function (day) {
            var any = false;
            day.querySelectorAll('.ts-row').forEach(function (row) {
                var show = visible(row);
                row.hidden = !show;
                if (show) any = true;
            });
            day.classList.toggle('is-filtered-out', !any && query !== '');
            if (query !== '') setDayOpen(day, any);
        });
        refresh();
    }

    function setDayOpen(day, open) {
        if (!day) return;
        day.classList.toggle('is-collapsed', !open);
        var body = day.querySelector('.ts-day-body');
        if (body) {
            if (open) body.removeAttribute('hidden');
            else body.setAttribute('hidden', '');
        }
        var toggle = day.querySelector('.ts-day-toggle');
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) bindSelects(day);
    }

    function openFromHash() {
        var id = (window.location.hash || '').replace('#', '');
        if (!id) return;
        var day = document.getElementById(id);
        if (!day || !day.classList.contains('ts-day')) return;
        setDayOpen(day, true);
        day.scrollIntoView({ block: 'nearest' });
    }

    function addRow(day, preset) {
        var body = day.querySelector('tbody');
        var source = preset && preset.kind === 'extra' ? extraTpl : tpl;
        if (!body || !source) return;
        var html = source.innerHTML.split('__I__').join('n' + (rowSeq++));
        var holder = document.createElement('tbody');
        holder.innerHTML = html.trim();
        var row = holder.querySelector('.ts-row');
        if (!row) return;
        body.appendChild(row);
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
            if (taskSelect && preset.ticket) setTaskValue(taskSelect, preset.ticket);
            applyTask(row);
            var hours = row.querySelector('.ts-hours');
            if (hours && preset.hours) hours.value = String(preset.hours);
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
        if (event.target.matches('.ts-task-select, .ts-hours')) applyFilters();
    });

    root.addEventListener('input', function (event) {
        if (event.target.matches('.ts-hours')) refresh();
    });

    root.addEventListener('click', function (event) {
        var chip = event.target.closest('a.ts-chip[href^="#ts-day-"]');
        if (chip) {
            var linked = document.getElementById((chip.getAttribute('href') || '').slice(1));
            if (linked) setDayOpen(linked, true);
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

    applyFilters();
    root.querySelectorAll('.ts-day:not(.is-collapsed)').forEach(function (day) { bindSelects(day); });
    openFromHash();
    window.addEventListener('hashchange', openFromHash);
})();
</script>
