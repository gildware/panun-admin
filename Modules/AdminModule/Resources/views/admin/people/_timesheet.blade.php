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
<div class="ts-app" id="ts-app" data-min-hours="{{ $board['minHours'] ?? 0 }}" data-leave-hours="{{ $board['leaveHours'] ?? 8 }}">
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
                <form method="post" action="{{ route('admin.people.timesheet.day') }}" id="ts-leave-form">
                    @csrf
                    <input type="hidden" name="intent" value="leave">
                    <input type="hidden" name="work_date" value="{{ $board['leaveDate'] }}">
                    <input type="hidden" name="month" value="{{ $board['month'] }}">
                    <button class="ts-leave-btn" type="submit">I was on Leave</button>
                </form>
                <div class="ts-tools">
                    <label class="ts-search">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 16l4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <input id="ts-search" type="search" placeholder="Search tasks" aria-label="Search tasks">
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
                    <form class="ts-day" id="ts-day-{{ $card['date'] }}" method="post" action="{{ route('admin.people.timesheet.day') }}" data-open="{{ $open ? '1' : '0' }}" data-date="{{ $card['date'] }}">
                        @csrf
                        <input type="hidden" name="intent" value="submit">
                        <input type="hidden" name="work_date" value="{{ $card['date'] }}">
                        <input type="hidden" name="month" value="{{ $board['month'] }}">
                        <div class="ts-day-head">
                            <h3>{{ $card['label'] }}</h3>
                            <p>Tasks <span class="ts-count"># <b data-tasks>{{ $taskCount }}</b></span></p>
                            <p>Total Time <b class="ts-day-total {{ $open ? 'is-open' : '' }}" data-day-total>{{ $fmt($rowHours, true) }}</b></p>
                            @if($open)
                                <button class="ts-submit" type="submit" @disabled($taskCount < 1 || (($board['minHours'] ?? 0) > 0 && $rowHours + 0.001 < (float) $board['minHours']))>Submit <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                            @elseif(($card['state'] ?? '') === 'done')
                                <span class="ts-submitted">Submitted</span>
                            @else
                                <span class="ts-submitted">With manager</span>
                            @endif
                        </div>
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
                                        @include('adminmodule::admin.people._timesheet_row', ['row' => $row, 'index' => $index, 'locked' => ! $open, 'tasks' => $board['tasks']])
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($open)
                            <div class="ts-day-add">
                                <button type="button" data-add="task">Add task <span>+</span></button>
                                <button type="button" data-add="leave">Add Leave hours <span>+</span></button>
                            </div>
                        @endif
                    </form>
                @empty
                    <p class="ts-empty">No working days to fill in {{ $board['monthLabel'] }}.</p>
                @endforelse
            </div>

            <footer class="ts-status">
                <mark>Timesheet</mark>
                <span>Status for this month (days)</span>
                <div class="ts-status-days">
                    @foreach($board['statusDays'] as $day)
                        @if(in_array($day['state'], ['pending', 'done'], true))
                            <a class="ts-chip is-{{ $day['state'] }}" href="#ts-day-{{ $day['date'] }}">{{ $day['n'] }}</a>
                        @else
                            <span class="ts-chip is-{{ $day['state'] }}">{{ $day['n'] }}</span>
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
    @include('adminmodule::admin.people._timesheet_row', ['row' => [], 'index' => '__I__', 'locked' => false, 'tasks' => $board['tasks']])
</template>

<script>
(function () {
    var root = document.getElementById('ts-app');
    if (!root || root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    var minHours = Number(root.getAttribute('data-min-hours')) || 0;
    var leaveHours = Number(root.getAttribute('data-leave-hours')) || 8;
    var tpl = document.getElementById('ts-row-tpl');
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
            placeholder: 'Select task'
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
            var count = totals.count;
            var hours = totals.hours;
            total += hours;
            var countNode = day.querySelector('[data-tasks]');
            var totalNode = day.querySelector('[data-day-total]');
            var submit = day.querySelector('.ts-submit');
            var short = minHours > 0 && hours + 0.001 < minHours;
            if (countNode) countNode.textContent = String(count);
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
        });
        refresh();
    }

    function addRow(day, preset) {
        var body = day.querySelector('tbody');
        if (!body || !tpl) return;
        var html = tpl.innerHTML.split('__I__').join('n' + (rowSeq++));
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
        if (!form.classList || !form.classList.contains('ts-day') || form.getAttribute('data-open') !== '1') return;
        if (dayCanSubmit(form)) return;
        event.preventDefault();
        var totals = dayTotals(form);
        if (minHours > 0 && totals.hours + 0.001 < minHours) {
            window.alert('A day needs at least ' + String(minHours) + ' hours.');
        }
    });

    root.addEventListener('change', function (event) {
        var row = event.target.closest('.ts-row');
        if (event.target.matches('.ts-task-select') && row) applyTask(row);
        if (event.target.matches('.ts-task-select, .ts-hours')) applyFilters();
    });

    root.addEventListener('input', function (event) {
        if (event.target.matches('.ts-hours')) refresh();
    });

    root.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add]');
        if (add) {
            var day = add.closest('.ts-day');
            if (!day) return;
            if (add.getAttribute('data-add') === 'leave') addRow(day, { ticket: 'partial-leave', label: 'Partial Leave', hours: 1 });
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

    var leaveForm = document.getElementById('ts-leave-form');
    if (leaveForm) {
        leaveForm.addEventListener('submit', function (event) {
            var day = root.querySelector('.ts-day[data-open="1"]');
            if (!day) return;
            event.preventDefault();
            var filled = false;
            day.querySelectorAll('.ts-hours').forEach(function (input) { if (Number(input.value) > 0) filled = true; });
            if (filled && !window.confirm('Replace this day with a full leave day?')) return;
            var body = day.querySelector('tbody');
            if (body) {
                body.querySelectorAll('.ts-task-select').forEach(function (select) {
                    if (window.jQuery && jQuery(select).data('select2')) jQuery(select).select2('destroy');
                });
                body.innerHTML = '';
            }
            addRow(day, { ticket: 'leave', label: 'Leave', hours: leaveHours });
            day.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    applyFilters();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { bindSelects(root); });
    } else {
        bindSelects(root);
    }
})();
</script>
