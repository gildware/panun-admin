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
                    @endphp
                    <div class="field" id="leave-hours-field" @if(! old('half_day')) hidden @endif>
                        <label for="leave_hours">Hours</label>
                        <input id="leave_hours" name="leave_hours" type="number" min="0.5" max="{{ $maxPartialHours }}" step="0.5" inputmode="decimal" value="{{ old('leave_hours', $halfHours) }}" @disabled(! old('half_day'))>
                        <p class="people-field-error" id="leave_hours_error" hidden></p>
                        <p class="people-ws-note" id="leave-hours-note">Starts at half of this employee's day ({{ \Modules\AdminModule\Services\PeopleWorkspace::hoursText($halfHours) }} hours). Change it for a shorter or longer part of the day.</p>
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
@push('script')
    <script>
        (function () {
            var modalEl = document.getElementById('applyLeaveModal');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    var pop = document.getElementById('people-date-pop');
                    if (pop) pop.hidden = true;
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

            function syncHalfDay() {
                var oneDate = !!(halfDay && halfDay.checked);
                if (dateRange) dateRange.classList.toggle('is-one-date', oneDate);
                if (fromLabel) fromLabel.textContent = oneDate ? 'Date' : 'From';
                if (toField) toField.hidden = oneDate;
                if (leaveHoursField) leaveHoursField.hidden = !oneDate;
                if (leaveHours) {
                    leaveHours.disabled = !oneDate;
                    if (oneDate && leaveHours.value === '') leaveHours.value = String(picker.halfHours || 4);
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
            watchField(leaveHours);
            watchField(reasonField);

            if (leaveForm) {
                leaveForm.addEventListener('submit', function (event) {
                    var half = !!(halfDay && halfDay.checked);
                    var firstInvalid = null;
                    function fail(input, message) {
                        setFieldError(input, message);
                        if (!firstInvalid) firstInvalid = input;
                    }

                    [startsOn, endsOn, leaveHours, reasonField].forEach(clearFieldError);

                    if (half && startsOn && endsOn) {
                        endsOn.disabled = false;
                        endsOn.value = startsOn.value;
                        if (leaveHours) leaveHours.disabled = false;
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

                    if (half && leaveHours) {
                        var dayMax = Number(picker.dayHours) || 8;
                        var entered = Number(leaveHours.value);
                        if (!entered || entered < 0.5 || entered > dayMax - 0.5 + 0.001) {
                            fail(leaveHours, 'Enter the leave hours. Half a day starts at ' + hoursLabel(picker.halfHours || dayMax / 2) + ', and it must stay under a full day.');
                        }
                    }

                    if (!reasonField || !String(reasonField.value || '').trim()) {
                        fail(reasonField, 'Enter a reason.');
                    }

                    if (firstInvalid) {
                        event.preventDefault();
                        if (half && endsOn) endsOn.disabled = true;
                        if (typeof firstInvalid.focus === 'function') firstInvalid.focus();
                    }
                });
            }
        })();
    </script>
@endpush
