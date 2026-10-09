@php
    $returnSection = ($returnSection ?? 'home') === 'timesheet' ? 'timesheet' : 'home';
    $leaveShort = $leaveTypes->mapWithKeys(fn ($type) => [$type->code => $type->short_name ?: strtoupper(substr($type->code, 0, 3))])->all();
    $applyOpen = old('starts_on') || old('ends_on') || old('leave_type') || old('reason');
@endphp
<div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form method="post" action="{{ route('admin.people.leave.store') }}" novalidate>
                @csrf
                <input type="hidden" name="return_section" value="{{ $returnSection }}">
                @if($returnSection === 'timesheet' && preg_match('/^\d{4}-\d{2}$/', (string) request('month')))
                    <input type="hidden" name="month" value="{{ request('month') }}">
                @endif
                <div class="modal-header">
                    <h2 class="modal-title" id="applyLeaveModalLabel">Apply Leaves</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <ul class="people-ws-errors">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="field"><label for="leave_type">Type</label>
                        <select id="leave_type" name="leave_type">
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->code }}" @selected(old('leave_type', $leaveTypes->first()->code ?? '') === $type->code)>{{ $leaveShort[$type->code] ?? strtoupper(substr($type->code, 0, 3)) }} · {{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="people-ws-note" id="leave-availability"></p>
                    <div class="people-ws-form-grid" id="leave-date-range">
                        <div class="field">
                            <label for="starts_on" id="starts_on_label">From</label>
                            <input id="starts_on" class="people-date-field" name="starts_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('starts_on') }}" readonly>
                            <p class="people-field-error" id="starts_on_error" hidden></p>
                        </div>
                        <div class="field" id="leave-to-field">
                            <label for="ends_on">To</label>
                            <input id="ends_on" class="people-date-field" name="ends_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('ends_on') }}" readonly>
                            <p class="people-field-error" id="ends_on_error" hidden></p>
                        </div>
                    </div>
                    <p class="people-leave-days" id="leave-day-count" hidden></p>
                    <div class="field"><label><input type="checkbox" id="half_day" name="half_day" value="1" @checked(old('half_day'))> Half day (one date)</label></div>
                    @php
                        $dayHours = (float) ($leavePicker['dayHours'] ?? 8);
                        $halfHours = (float) ($leavePicker['halfHours'] ?? round($dayHours / 2, 1));
                        $maxPartialHours = max(0.5, round($dayHours - 0.5, 1));
                        $halfMinutes = (int) round($halfHours * 60);
                        $defaultEnd = min((9 * 60) + $halfMinutes, (23 * 60) + 59);
                        $defaultFrom = old('from_time', '09:00');
                        $defaultTo = old('to_time', sprintf('%02d:%02d', intdiv($defaultEnd, 60), $defaultEnd % 60));
                    @endphp
                    <div class="people-ws-form-grid" id="leave-time-range" @if(! old('half_day')) hidden @endif>
                        <div class="field">
                            <label for="from_time_entry">From time</label>
                            <input id="from_time" name="from_time" type="hidden" value="{{ $defaultFrom }}" @disabled(! old('half_day'))>
                            <div class="people-time-control">
                                <input id="from_time_entry" class="people-time-entry" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="09:00 AM" @disabled(! old('half_day'))>
                                <button type="button" class="people-time-btn" id="from_time_btn" data-time-for="from_time" aria-label="Choose from time" @disabled(! old('half_day'))>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4.5l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </button>
                            </div>
                            <p class="people-field-error" id="from_time_error" hidden></p>
                        </div>
                        <div class="field">
                            <label for="to_time_entry">To time</label>
                            <input id="to_time" name="to_time" type="hidden" value="{{ $defaultTo }}" @disabled(! old('half_day'))>
                            <div class="people-time-control">
                                <input id="to_time_entry" class="people-time-entry" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="01:00 PM" @disabled(! old('half_day'))>
                                <button type="button" class="people-time-btn" id="to_time_btn" data-time-for="to_time" aria-label="Choose to time" @disabled(! old('half_day'))>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 8v4.5l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                </button>
                            </div>
                            <p class="people-field-error" id="to_time_error" hidden></p>
                        </div>
                    </div>
                    <div class="field" id="leave-hours-field" @if(! old('half_day')) hidden @endif>
                        <label for="leave_hours">Hours</label>
                        <input id="leave_hours" name="leave_hours" type="number" min="0.5" max="{{ $maxPartialHours }}" step="0.1" inputmode="decimal" value="{{ old('leave_hours', $halfHours) }}" readonly @disabled(! old('half_day'))>
                        <p class="people-field-error" id="leave_hours_error" hidden></p>
                        <p class="people-ws-note" id="leave-hours-note">Hours follow the from and to time. A part of the day must stay under a full day.</p>
                    </div>
                    <div class="field">
                        <label for="reason">Reason</label>
                        <textarea id="reason" name="reason">{{ old('reason') }}</textarea>
                        <p class="people-field-error" id="reason_error" hidden></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit">Send request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="people-time-pop" id="people-time-pop" hidden>
    <input id="people-time-typed" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="10:30 AM" aria-label="Type a time">
    <p class="people-time-hint">Type a time, or pick hour, minute, and AM/PM. The list stays open until you press Done.</p>
    <div class="people-time-block">
        <span>Hour</span>
        <div class="people-time-grid" data-time-list="hour">
            @for ($timeHour = 1; $timeHour <= 12; $timeHour++)
                <button type="button" data-value="{{ $timeHour }}">{{ sprintf('%02d', $timeHour) }}</button>
            @endfor
        </div>
    </div>
    <div class="people-time-block">
        <span>Minute</span>
        <div class="people-time-grid" data-time-list="minute">
            @for ($timeMinute = 0; $timeMinute < 60; $timeMinute += 5)
                <button type="button" data-value="{{ $timeMinute }}">{{ sprintf('%02d', $timeMinute) }}</button>
            @endfor
        </div>
    </div>
    <div class="people-time-block">
        <span>AM / PM</span>
        <div class="people-time-meridiem">
            <button type="button" data-meridiem="AM">AM</button>
            <button type="button" data-meridiem="PM">PM</button>
        </div>
    </div>
    <div class="people-time-actions">
        <button type="button" data-time-cancel>Cancel</button>
        <button type="button" data-time-done>Done</button>
    </div>
</div>
@push('script')
    <script>
        (function () {
            var modalEl = document.getElementById('applyLeaveModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    var pop = document.getElementById('people-date-pop');
                    if (pop) pop.hidden = true;
                    var timePop = document.getElementById('people-time-pop');
                    if (timePop) timePop.hidden = true;
                });
            }

            @if($applyOpen)
            if (modalEl && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
            @endif

            var halfDay = document.getElementById('half_day');
            var leaveHoursField = document.getElementById('leave-hours-field');
            var leaveHours = document.getElementById('leave_hours');
            var timeRange = document.getElementById('leave-time-range');
            var fromTime = document.getElementById('from_time');
            var toTime = document.getElementById('to_time');
            var leaveType = document.getElementById('leave_type');
            var leaveForm = modalEl ? modalEl.querySelector('form') : null;
            var dateRange = document.getElementById('leave-date-range');
            var fromLabel = document.getElementById('starts_on_label');
            var toField = document.getElementById('leave-to-field');
            var startsOn = document.getElementById('starts_on');
            var endsOn = document.getElementById('ends_on');
            var reasonField = document.getElementById('reason');
            var availabilityNote = document.getElementById('leave-availability');
            var dayCountNote = document.getElementById('leave-day-count');
            var picker = @json($leavePicker);
            if (!picker) picker = { weekOff: ['sun'], holidays: {}, blocked: [], types: {} };
            var weekOff = picker.weekOff || ['sun'];
            var holidays = picker.holidays || {};
            var blocked = picker.blocked || [];

            function parseIso(value) {
                var parts = String(value).split('-');
                return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            }

            function isoOf(date) {
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
            }

            function todayIso() {
                var now = new Date();
                return isoOf(new Date(now.getFullYear(), now.getMonth(), now.getDate()));
            }

            function yearOf(value) {
                return parseIso(value).getFullYear();
            }

            function typeInfo() {
                var code = leaveType ? leaveType.value : '';
                return (picker.types && picker.types[code]) || { tracks: true, future: true, name: 'Leave', years: {} };
            }

            function daysLeft(info, year) {
                var years = info.years || {};
                var value = years[String(year)];
                return value == null ? 0 : Number(value);
            }

            function daysLabel(days) {
                var rounded = Math.round(days * 10) / 10;
                var text = Math.abs(rounded - Math.round(rounded)) < 0.001 ? String(Math.round(rounded)) : rounded.toFixed(1);
                return text + (text === '1' ? ' day' : ' days');
            }

            function isOff(value) {
                var date = parseIso(value);
                var key = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'][(date.getDay() + 6) % 7];
                if (weekOff.indexOf(key) !== -1) return 'Week off';
                if (holidays[value]) return holidays[value];
                return '';
            }

            function overlaps(from, to) {
                return blocked.some(function (range) {
                    return from <= range.end && to >= range.start;
                });
            }

            function partialDays() {
                var day = Number(picker.dayHours) || 8;
                var hours = leaveHours ? Number(leaveHours.value) : Number(picker.halfHours);
                if (!hours || hours <= 0) hours = Number(picker.halfHours) || day / 2;
                return Math.max(0.1, Math.min(0.9, Math.round((hours / day) * 10) / 10));
            }

            function hoursLabel(hours) {
                var rounded = Math.round((Number(hours) || 0) * 10) / 10;
                var text = Math.abs(rounded - Math.round(rounded)) < 0.001 ? String(Math.round(rounded)) : rounded.toFixed(1);
                return text + (text === '1' ? ' hour' : ' hours');
            }

            function overBalance(from, to, half) {
                var info = typeInfo();
                if (half) {
                    if (isOff(from)) return true;
                    if (!info.tracks) return false;
                    return partialDays() > daysLeft(info, yearOf(from)) + 0.001;
                }
                var counts = {};
                var total = 0;
                var cursor = parseIso(from);
                var end = parseIso(to);
                var guard = 0;
                while (cursor <= end && guard < 800) {
                    var iso = isoOf(cursor);
                    if (!isOff(iso)) {
                        var year = cursor.getFullYear();
                        counts[year] = (counts[year] || 0) + 1;
                        total += 1;
                        if (info.tracks && counts[year] > daysLeft(info, year) + 0.001) return true;
                    }
                    cursor.setDate(cursor.getDate() + 1);
                    guard++;
                }
                return total <= 0;
            }

            window.peopleDateBlockReason = function (input, value) {
                if (!input || (input.id !== 'starts_on' && input.id !== 'ends_on')) return '';
                var info = typeInfo();
                var half = !!(halfDay && halfDay.checked);
                var off = isOff(value);
                if (off) return off;
                if (info.future === false && value > todayIso()) return info.name + ' cannot be applied for a future date';
                if (overlaps(value, value)) return 'Already on a leave request';
                if (half || input.id === 'starts_on') {
                    if (info.tracks) {
                        var need = half ? partialDays() : 1;
                        var have = daysLeft(info, yearOf(value));
                        if (have + 0.001 < need) {
                            return have <= 0
                                ? 'No ' + info.name + ' leave left in ' + yearOf(value)
                                : 'Not enough ' + info.name + ' leave left';
                        }
                    }
                    if (!half && input.id === 'starts_on' && endsOn && endsOn.value) {
                        if (value > endsOn.value) return 'Starts after the end date';
                        if (overlaps(value, endsOn.value)) return 'Overlaps a leave request';
                        if (overBalance(value, endsOn.value, false)) return 'More days than you have left';
                    }
                    return '';
                }
                if (!startsOn || !startsOn.value) return 'Choose the from date first';
                if (value < startsOn.value) return 'Before the from date';
                if (overlaps(startsOn.value, value)) return 'Overlaps a leave request';
                if (overBalance(startsOn.value, value, false)) return 'More days than you have left';
                return '';
            };

            function updateAvailability() {
                if (!availabilityNote) return;
                var info = typeInfo();
                var year = new Date().getFullYear();
                if (!info.tracks) {
                    availabilityNote.textContent = info.name + ' is not limited by a balance. Week offs, holidays, and days already requested stay closed.';
                    return;
                }
                var have = daysLeft(info, year);
                if (have <= 0) {
                    availabilityNote.textContent = 'No ' + info.name + ' leave left in ' + year + '. Those dates are closed.';
                    return;
                }
                if (have < 1) {
                    availabilityNote.textContent = daysLabel(have) + ' of ' + info.name + ' left in ' + year + '. Use half day. A full day needs more than that, so those dates stay closed.';
                    return;
                }
                availabilityNote.textContent = daysLabel(have) + ' of ' + info.name + ' left in ' + year + '. You can pick that many working days. Dates past that stay closed.';
            }

            function requestedSpan() {
                var half = !!(halfDay && halfDay.checked);
                var from = startsOn ? startsOn.value : '';
                var to = half ? from : (endsOn ? endsOn.value : '');
                if (!from || !to || to < from) return null;
                if (half) {
                    return { days: isOff(from) ? 0 : partialDays(), holidays: [], weekOffs: 0, hours: leaveHours ? Number(leaveHours.value) : Number(picker.halfHours) };
                }
                var days = 0;
                var holidayNames = [];
                var weekOffs = 0;
                var cursor = parseIso(from);
                var end = parseIso(to);
                var guard = 0;
                while (cursor <= end && guard < 800) {
                    var off = isOff(isoOf(cursor));
                    if (off === 'Week off') weekOffs++;
                    else if (off) holidayNames.push(off);
                    else days++;
                    cursor.setDate(cursor.getDate() + 1);
                    guard++;
                }
                return { days: days, holidays: holidayNames, weekOffs: weekOffs };
            }

            function updateDayCount() {
                if (!dayCountNote) return;
                var span = requestedSpan();
                if (!span) {
                    dayCountNote.hidden = true;
                    dayCountNote.textContent = '';
                    return;
                }
                var half = !!(halfDay && halfDay.checked);
                var text = half
                    ? 'You are applying for ' + hoursLabel(span.hours) + '.'
                    : 'You are applying for ' + daysLabel(span.days) + '.';
                var skipped = [];
                if (span.holidays.length) skipped.push(span.holidays.join(', '));
                if (span.weekOffs === 1) skipped.push('1 week off');
                else if (span.weekOffs > 1) skipped.push(span.weekOffs + ' week offs');
                if (skipped.length) text += ' Not counted: ' + skipped.join(', ') + '.';
                dayCountNote.hidden = false;
                dayCountNote.textContent = text;
            }

            function keepRangeInsideBalance() {
                if (!startsOn || !endsOn) return;
                var savedEnd = endsOn.value;
                endsOn.value = '';
                var startReason = startsOn.value ? window.peopleDateBlockReason(startsOn, startsOn.value) : '';
                endsOn.value = savedEnd;
                if (startReason) {
                    startsOn.value = '';
                    endsOn.value = '';
                    return;
                }
                if (halfDay && halfDay.checked) {
                    if (startsOn.value) endsOn.value = startsOn.value;
                    return;
                }
                if (startsOn.value && (!endsOn.value || endsOn.value < startsOn.value || window.peopleDateBlockReason(endsOn, endsOn.value))) {
                    endsOn.value = startsOn.value;
                }
            }

            function clockMinutes(value) {
                var parts = String(value || '').split(':');
                if (parts.length < 2 || parts[0] === '' || parts[1] === '') return null;
                var hours = Number(parts[0]);
                var minutes = Number(parts[1]);
                if (!isFinite(hours) || !isFinite(minutes) || hours > 23 || minutes > 59) return null;
                return (hours * 60) + minutes;
            }

            var timePop = document.getElementById('people-time-pop');
            var timeTyped = document.getElementById('people-time-typed');
            var activeTimeInput = null;
            var activeTimeButton = null;
            var timeDraft = { hour: '', minute: '', meridiem: '' };
            var timeTyping = false;

            function formatClock(value) {
                var total = clockMinutes(value);
                if (total === null) return '';
                var hour24 = Math.floor(total / 60);
                var minute = total % 60;
                var hour12 = hour24 % 12 || 12;
                return String(hour12).padStart(2, '0') + ':' + String(minute).padStart(2, '0') + ' ' + (hour24 >= 12 ? 'PM' : 'AM');
            }

            function parseClock(text, requireMeridiem) {
                var raw = String(text || '').trim().toUpperCase().replace(/\s+/g, ' ');
                if (!raw || raw === '--:--') return null;
                var mer = null;
                var merMatch = raw.match(/\s*(A\.?M\.?|P\.?M\.?)$/);
                if (merMatch) {
                    mer = merMatch[1].charAt(0) === 'A' ? 'AM' : 'PM';
                    raw = raw.slice(0, merMatch.index).trim();
                }
                raw = raw.replace(/\./g, ':');
                var hour = null;
                var minute = null;
                var clock = raw.match(/^(\d{1,2}):(\d{2})$/);
                if (clock) {
                    hour = Number(clock[1]);
                    minute = Number(clock[2]);
                } else if (/^\d{3,4}$/.test(raw)) {
                    hour = Number(raw.length === 3 ? raw.slice(0, 1) : raw.slice(0, 2));
                    minute = Number(raw.length === 3 ? raw.slice(1) : raw.slice(2));
                } else if (/^\d{1,2}$/.test(raw) && mer) {
                    hour = Number(raw);
                    minute = 0;
                } else {
                    return null;
                }
                if (!isFinite(hour) || !isFinite(minute) || minute > 59 || hour < 0 || hour > 23) return null;
                if (mer) {
                    if (hour < 1 || hour > 12) return null;
                    hour = hour % 12;
                    if (mer === 'PM') hour += 12;
                } else if (requireMeridiem && hour < 13) {
                    return null;
                }
                return String(hour).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
            }

            function draftFromValue(value) {
                var total = clockMinutes(value);
                if (total === null) return { hour: '', minute: '', meridiem: '' };
                var hour24 = Math.floor(total / 60);
                return {
                    hour: String(hour24 % 12 || 12),
                    minute: String(total % 60),
                    meridiem: hour24 >= 12 ? 'PM' : 'AM'
                };
            }

            function draftValue() {
                if (timeDraft.hour === '' || timeDraft.minute === '' || !timeDraft.meridiem) return null;
                var hour12 = Number(timeDraft.hour);
                var minute = Number(timeDraft.minute);
                if (!isFinite(hour12) || hour12 < 1 || hour12 > 12 || minute < 0 || minute > 59) return null;
                var hour24 = hour12 % 12;
                if (timeDraft.meridiem === 'PM') hour24 += 12;
                return String(hour24).padStart(2, '0') + ':' + String(minute).padStart(2, '0');
            }

            function draftLabel() {
                if (timeDraft.hour === '' && timeDraft.minute === '' && !timeDraft.meridiem) return '';
                var hour = timeDraft.hour === '' ? '--' : String(timeDraft.hour).padStart(2, '0');
                var minute = timeDraft.minute === '' ? '--' : String(Number(timeDraft.minute)).padStart(2, '0');
                return hour + ':' + minute + (timeDraft.meridiem ? ' ' + timeDraft.meridiem : '');
            }

            function timeInputFor(button) {
                if (!button) return null;
                var id = button.getAttribute('data-time-for');
                if (id) {
                    var named = document.getElementById(id);
                    if (named) return named;
                }
                var field = button.closest('.field, .ts-field');
                if (!field) return null;
                return field.querySelector('input.ts-from, input.ts-to, input[type="hidden"]');
            }

            function entryFor(input) {
                if (!input) return null;
                var field = input.closest('.field, .ts-field');
                return field ? field.querySelector('.people-time-entry') : null;
            }

            function hiddenForEntry(entry) {
                var field = entry && entry.closest('.field, .ts-field');
                if (!field) return null;
                return field.querySelector('input.ts-from, input.ts-to, input[type="hidden"]');
            }

            function paintTimeButtons(scope) {
                (scope || document).querySelectorAll('.people-time-btn').forEach(function (button) {
                    var input = timeInputFor(button);
                    var entry = entryFor(input);
                    if (!input) return;
                    button.disabled = input.disabled;
                    if (entry) {
                        entry.disabled = input.disabled;
                        if (document.activeElement !== entry) entry.value = formatClock(input.value);
                    }
                });
            }

            function markTimeDraft() {
                if (!timePop) return;
                timePop.querySelectorAll('[data-time-list="hour"] button').forEach(function (button) {
                    button.classList.toggle('is-selected', timeDraft.hour !== '' && Number(button.getAttribute('data-value')) === Number(timeDraft.hour));
                });
                timePop.querySelectorAll('[data-time-list="minute"] button').forEach(function (button) {
                    button.classList.toggle('is-selected', timeDraft.minute !== '' && Number(button.getAttribute('data-value')) === Number(timeDraft.minute));
                });
                timePop.querySelectorAll('[data-meridiem]').forEach(function (button) {
                    button.classList.toggle('is-selected', button.getAttribute('data-meridiem') === timeDraft.meridiem);
                });
                var done = timePop.querySelector('[data-time-done]');
                if (done) done.disabled = !draftValue();
            }

            function showTimeDraft() {
                markTimeDraft();
                if (timeTyped && !timeTyping) timeTyped.value = draftLabel();
            }

            function placeTimePop(button) {
                if (!timePop) return;
                var anchor = button.closest('.people-time-control') || button;
                var rect = anchor.getBoundingClientRect();
                var width = timePop.offsetWidth;
                var height = timePop.offsetHeight;
                var left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);
                var top = rect.bottom + 6;
                if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
                timePop.style.left = left + 'px';
                timePop.style.top = top + 'px';
            }

            function closeTimePop() {
                if (timePop) timePop.hidden = true;
                activeTimeInput = null;
                activeTimeButton = null;
                timeTyping = false;
            }

            function openTimePop(button) {
                var input = timeInputFor(button);
                if (!timePop || !input || input.disabled || button.disabled) return;
                if (activeTimeInput === input && !timePop.hidden) return;
                activeTimeInput = input;
                activeTimeButton = button;
                timeDraft = draftFromValue(input.value);
                timeTyping = false;
                timePop.hidden = false;
                showTimeDraft();
                placeTimePop(button);
                if (timeTyped) timeTyped.focus();
            }

            function commitTimePop() {
                var value = draftValue();
                if (!value || !activeTimeInput) return;
                activeTimeInput.value = value;
                activeTimeInput.dispatchEvent(new Event('input', { bubbles: true }));
                activeTimeInput.dispatchEvent(new Event('change', { bubbles: true }));
                paintTimeButtons();
                closeTimePop();
            }

            function commitEntry(entry) {
                var input = hiddenForEntry(entry);
                if (!entry || !input || input.disabled) return;
                var text = entry.value.trim();
                if (text === '') {
                    if (input.value !== '') {
                        input.value = '';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    return;
                }
                var parsed = parseClock(text, false);
                if (!parsed) {
                    entry.value = formatClock(input.value);
                    return;
                }
                entry.value = formatClock(parsed);
                if (input.value !== parsed) {
                    input.value = parsed;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            function syncLeaveSpan() {
                if (!leaveHours || !fromTime || !toTime) return null;
                var start = clockMinutes(fromTime.value);
                var end = clockMinutes(toTime.value);
                if (start === null || end === null || end <= start) return null;
                var hours = Math.round(((end - start) / 60) * 10) / 10;
                leaveHours.value = String(hours);
                return hours;
            }

            function syncHalfDay() {
                var oneDate = !!(halfDay && halfDay.checked);
                if (dateRange) dateRange.classList.toggle('is-one-date', oneDate);
                if (fromLabel) fromLabel.textContent = oneDate ? 'Date' : 'From';
                if (toField) toField.hidden = oneDate;
                if (timeRange) timeRange.hidden = !oneDate;
                if (!oneDate && timePop) timePop.hidden = true;
                if (fromTime) fromTime.disabled = !oneDate;
                if (toTime) toTime.disabled = !oneDate;
                paintTimeButtons();
                if (leaveHoursField) leaveHoursField.hidden = !oneDate;
                if (leaveHours) {
                    leaveHours.disabled = !oneDate;
                    if (oneDate) syncLeaveSpan();
                }
                if (endsOn) {
                    endsOn.disabled = oneDate;
                    if (oneDate && startsOn) endsOn.value = startsOn.value;
                }
                keepRangeInsideBalance();
                updateAvailability();
                updateDayCount();
                if (window.peopleDateRefresh) window.peopleDateRefresh();
            }

            if (halfDay) {
                halfDay.addEventListener('change', syncHalfDay);
                syncHalfDay();
            } else {
                updateAvailability();
                updateDayCount();
            }
            if (leaveType) {
                leaveType.addEventListener('change', function () {
                    keepRangeInsideBalance();
                    updateAvailability();
                    updateDayCount();
                    if (window.peopleDateRefresh) window.peopleDateRefresh();
                });
            }
            if (startsOn) {
                startsOn.addEventListener('change', function () {
                    keepRangeInsideBalance();
                    updateAvailability();
                    updateDayCount();
                });
            }
            if (endsOn) {
                endsOn.addEventListener('change', function () {
                    updateDayCount();
                });
            }
            function onLeaveTime() {
                syncLeaveSpan();
                updateDayCount();
                if (window.peopleDateRefresh) window.peopleDateRefresh();
            }
            if (fromTime) fromTime.addEventListener('input', onLeaveTime);
            if (fromTime) fromTime.addEventListener('change', onLeaveTime);
            if (toTime) toTime.addEventListener('input', onLeaveTime);
            if (toTime) toTime.addEventListener('change', onLeaveTime);
            if (leaveHours) {
                leaveHours.addEventListener('input', function () {
                    updateDayCount();
                    if (window.peopleDateRefresh) window.peopleDateRefresh();
                });
            }
            function clearFieldError(input) {
                if (!input) return;
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
                var wrap = input.closest('.field');
                var msg = wrap ? wrap.querySelector('.people-field-error') : null;
                if (msg) {
                    msg.hidden = true;
                    msg.textContent = '';
                }
            }

            function setFieldError(input, message) {
                if (!input) return;
                input.classList.add('is-invalid');
                input.setAttribute('aria-invalid', 'true');
                var wrap = input.closest('.field');
                var msg = wrap ? wrap.querySelector('.people-field-error') : null;
                if (msg) {
                    msg.hidden = false;
                    msg.textContent = message;
                }
            }

            function watchField(input) {
                if (!input) return;
                var clear = function () { clearFieldError(input); };
                input.addEventListener('input', clear);
                input.addEventListener('change', clear);
            }

            watchField(startsOn);
            watchField(endsOn);
            watchField(fromTime);
            watchField(toTime);
            watchField(leaveHours);
            watchField(reasonField);

            if (timePop) {
                timePop.addEventListener('mousedown', function (event) { event.stopPropagation(); });
                timePop.addEventListener('click', function (event) {
                    event.stopPropagation();
                    var choice = event.target.closest('button[data-value]');
                    var list = choice && choice.closest('[data-time-list]');
                    var meridiem = event.target.closest('[data-meridiem]');
                    if (choice && list) {
                        timeTyping = false;
                        if (list.getAttribute('data-time-list') === 'hour') timeDraft.hour = String(choice.getAttribute('data-value'));
                        else timeDraft.minute = String(choice.getAttribute('data-value'));
                        showTimeDraft();
                        return;
                    }
                    if (meridiem) {
                        timeTyping = false;
                        timeDraft.meridiem = meridiem.getAttribute('data-meridiem');
                        showTimeDraft();
                        return;
                    }
                    if (event.target.closest('[data-time-done]')) commitTimePop();
                    else if (event.target.closest('[data-time-cancel]')) closeTimePop();
                });
            }
            function applyTypedDraft() {
                if (!timeTyped) return;
                var withMeridiem = parseClock(timeTyped.value, true);
                if (withMeridiem) {
                    timeDraft = draftFromValue(withMeridiem);
                    return;
                }
                var loose = parseClock(timeTyped.value, false);
                if (!loose) return;
                var hour24 = Number(loose.slice(0, 2));
                if (hour24 >= 13 || hour24 === 0) {
                    timeDraft = draftFromValue(loose);
                    return;
                }
                var parts = draftFromValue(loose);
                timeDraft.hour = parts.hour;
                timeDraft.minute = parts.minute;
            }

            if (timeTyped) {
                timeTyped.addEventListener('input', function () {
                    timeTyping = true;
                    applyTypedDraft();
                    markTimeDraft();
                });
                timeTyped.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter') return;
                    event.preventDefault();
                    timeTyping = false;
                    applyTypedDraft();
                    if (draftValue()) commitTimePop();
                });
            }
            document.addEventListener('click', function (event) {
                var button = event.target.closest('.people-time-btn');
                if (button) {
                    event.preventDefault();
                    var nextInput = timeInputFor(button);
                    if (timePop && !timePop.hidden && activeTimeInput && activeTimeInput !== nextInput) {
                        if (draftValue()) commitTimePop();
                        else closeTimePop();
                    }
                    openTimePop(button);
                    return;
                }
                if (!timePop || timePop.hidden) return;
                if (event.target.closest('#people-time-pop')) return;
                if (draftValue()) commitTimePop();
                else closeTimePop();
            });
            document.addEventListener('input', function (event) {
                if (!event.target.classList || !event.target.classList.contains('people-time-entry')) return;
                var parsed = parseClock(event.target.value, false);
                var input = hiddenForEntry(event.target);
                if (!input || !parsed || input.value === parsed) return;
                input.value = parsed;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
            document.addEventListener('focusout', function (event) {
                if (!event.target.classList || !event.target.classList.contains('people-time-entry')) return;
                commitEntry(event.target);
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && timePop && !timePop.hidden) {
                    closeTimePop();
                    return;
                }
                if (event.key === 'Enter' && event.target.classList && event.target.classList.contains('people-time-entry')) {
                    event.preventDefault();
                    commitEntry(event.target);
                }
            });
            document.addEventListener('scroll', function () {
                if (timePop && !timePop.hidden && activeTimeButton) placeTimePop(activeTimeButton);
            }, true);
            paintTimeButtons();
            window.peopleTimePaint = paintTimeButtons;

            if (leaveForm) {
                leaveForm.addEventListener('submit', function (event) {
                    var half = !!(halfDay && halfDay.checked);
                    var firstInvalid = null;
                    function fail(input, message) {
                        setFieldError(input, message);
                        if (!firstInvalid) firstInvalid = input;
                    }

                    [startsOn, endsOn, fromTime, toTime, leaveHours, reasonField].forEach(clearFieldError);

                    if (half && startsOn && endsOn) {
                        endsOn.disabled = false;
                        endsOn.value = startsOn.value;
                        if (fromTime) fromTime.disabled = false;
                        if (toTime) toTime.disabled = false;
                        if (leaveHours) leaveHours.disabled = false;
                        syncLeaveSpan();
                    }

                    if (!startsOn || !String(startsOn.value || '').trim()) {
                        fail(startsOn, half ? 'Select a date.' : 'Select a start date.');
                    } else {
                        var blockedStart = window.peopleDateBlockReason(startsOn, startsOn.value);
                        if (blockedStart) fail(startsOn, blockedStart);
                    }

                    if (!half) {
                        if (!endsOn || !String(endsOn.value || '').trim()) {
                            fail(endsOn, 'Select an end date.');
                        } else {
                            var blockedEnd = window.peopleDateBlockReason(endsOn, endsOn.value);
                            if (blockedEnd) fail(endsOn, blockedEnd);
                        }
                    }

                    if (half) {
                        var startMinutes = fromTime ? clockMinutes(fromTime.value) : null;
                        var endMinutes = toTime ? clockMinutes(toTime.value) : null;
                        if (startMinutes === null) fail(fromTime, 'Enter a from time.');
                        if (endMinutes === null) fail(toTime, 'Enter a to time.');
                        if (startMinutes !== null && endMinutes !== null && endMinutes <= startMinutes) {
                            fail(toTime, 'To time has to be later than from time.');
                        }
                        var dayMax = Number(picker.dayHours) || 8;
                        var entered = leaveHours ? Number(leaveHours.value) : 0;
                        if (startMinutes !== null && endMinutes !== null && endMinutes > startMinutes && (!entered || entered < 0.5 || entered > dayMax - 0.5 + 0.001)) {
                            fail(toTime, 'That span must be at least half an hour and stay under a full day.');
                        }
                    }

                    if (!reasonField || !String(reasonField.value || '').trim()) {
                        fail(reasonField, 'Enter a reason.');
                    }

                    if (firstInvalid) {
                        event.preventDefault();
                        if (half && endsOn) endsOn.disabled = true;
                        var focusEl = firstInvalid.type === 'hidden' ? document.getElementById(firstInvalid.id + '_btn') : firstInvalid;
                        if (focusEl && typeof focusEl.focus === 'function') focusEl.focus();
                    }
                });
            }
        })();
    </script>
@endpush
