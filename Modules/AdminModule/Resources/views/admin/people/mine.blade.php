@extends('adminmodule::layouts.new-master')

@section('title', 'My workspace')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
@php
    $name = $workspace->displayName($actor);
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $hours = $timesheet->hours ?? [];
    $dayLabels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat'];
@endphp
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'mode' => 'mine',
            'section' => $section,
            'baseUrl' => route('admin.people.index'),
            'links' => [
                'home' => 'Home',
                'details' => 'My details',
                'documents' => 'Documents',
                'payslips' => 'Payslips',
                'timesheet' => 'Timesheet',
            ],
        ])

        @if($section === 'home')
            @php
                $leaveShort = $leaveTypes->mapWithKeys(fn ($type) => [$type->code => $type->short_name ?: strtoupper(substr($type->code, 0, 3))])->all();
                $pad = function ($n): string {
                    $n = (float) $n;

                    return abs($n - round($n)) < 0.001 ? sprintf('%02d', (int) round($n)) : number_format($n, 1);
                };
                $totalAllowance = 0;
                $totalUsed = 0;
                foreach ($leaveTypes->where('tracks_balance', true) as $type) {
                    $totalAllowance += $balance->allowance($type->code);
                    $totalUsed += $balance->used($type->code);
                }
                $applyOpen = $errors->any() || old('starts_on');
            @endphp
            <div class="people-ws-head">
                <div>
                    <h1>{{ $greeting }}, {{ strtok($name, ' ') }}</h1>
                    <p>{{ now()->format('l, j F Y') }}{{ $profile->work_location ? ' · '.$profile->work_location : '' }}</p>
                </div>
            </div>
            <div class="people-lh">
                <article class="people-lh-total">
                    <div class="people-lh-total-top">
                        <h2>Total<br>Leaves</h2>
                        <div class="people-lh-score">{{ $pad($totalAllowance) }}<span>/{{ $pad($totalUsed) }}</span></div>
                    </div>
                    <div class="people-lh-fill">
                        @foreach($leaveTypes as $type)
                            <div class="people-lh-line">
                                <em>{{ $leaveShort[$type->code] ?? strtoupper(substr($type->code, 0, 3)) }}</em>
                                <div class="people-lh-score">
                                    @if($type->tracks_balance)
                                        {{ $pad($balance->allowance($type->code)) }}<span>/{{ $pad($balance->used($type->code)) }}</span>
                                    @else
                                        —<span></span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
                <article class="people-lh-holidays">
                    <h2>Holidays</h2>
                    <div class="people-lh-fill">
                        @forelse($holidays as $holiday)
                            <div class="people-lh-holiday" @if($holiday->description) title="{{ $holiday->description }}" @endif>
                                <strong>{{ $holiday->name }}</strong>
                                <span>{{ $holiday->holiday_on->format('j M, D') }}</span>
                            </div>
                        @empty
                            <p class="people-ws-note">No holidays yet.</p>
                        @endforelse
                    </div>
                </article>
                <article class="people-lh-status">
                    <button class="people-lh-apply" type="button" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">Apply Leaves</button>
                    <h2>Leave Status</h2>
                    <div class="people-lh-head"><span>Start</span><span>End</span><span>Reason</span><span>Type</span><span>Status</span><span></span></div>
                    <div class="people-lh-fill">
                        @forelse($leaveRequests as $leave)
                            <div class="people-lh-row">
                                <span title="{{ $leave->starts_on->format('j M Y') }}">{{ $leave->starts_on->format('j M Y') }}</span>
                                <span title="{{ $leave->ends_on->format('j M Y') }}">{{ $leave->ends_on->format('j M Y') }}</span>
                                <span class="people-lh-reason" title="{{ $leave->reason }}">{{ $leave->reason }}</span>
                                <span>{{ $leaveShort[$leave->leave_type] ?? $workspace->leaveLabel($leave->leave_type) }}</span>
                                <span class="people-lh-pill is-{{ $leave->status }}">{{ $workspace->statusLabel($leave->status) }}</span>
                                @if($leave->status === 'pending')
                                    <button
                                        class="people-lh-revoke"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#revokeLeaveModal"
                                        data-action="{{ route('admin.people.leave.cancel', $leave) }}"
                                        data-summary="{{ $leaveShort[$leave->leave_type] ?? $workspace->leaveLabel($leave->leave_type) }} · {{ $leave->starts_on->format('j M Y') }} – {{ $leave->ends_on->format('j M Y') }}"
                                    >Revoke</button>
                                @else
                                    <span></span>
                                @endif
                            </div>
                        @empty
                            <p class="people-ws-note">No leave requests yet.</p>
                        @endforelse
                    </div>
                </article>
            </div>
            <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content people-ws-dept-modal">
                        <form method="post" action="{{ route('admin.people.leave.store') }}">
                            @csrf
                            <input type="hidden" name="return_section" value="home">
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
                                            <option value="{{ $type->code }}" @selected(old('leave_type', 'casual') === $type->code)>{{ $leaveShort[$type->code] ?? strtoupper(substr($type->code, 0, 3)) }} · {{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="people-ws-form-grid">
                                    <div class="field"><label for="starts_on">From</label><input id="starts_on" class="people-date-field" name="starts_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('starts_on') }}" required readonly></div>
                                    <div class="field"><label for="ends_on">To</label><input id="ends_on" class="people-date-field" name="ends_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('ends_on') }}" required readonly></div>
                                </div>
                                <div class="field"><label><input type="checkbox" name="half_day" value="1" @checked(old('half_day'))> Half day (one date)</label></div>
                                <div class="field"><label for="reason">Reason</label><textarea id="reason" name="reason" required>{{ old('reason') }}</textarea></div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn-pw primary" type="submit">Send request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="revokeLeaveModal" tabindex="-1" aria-labelledby="revokeLeaveModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content people-ws-dept-modal">
                        <form id="revoke-leave-form" method="post" action="">
                            @csrf
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
            <div class="people-date-pop" id="people-date-pop" hidden>
                <div class="people-date-pop-bar">
                    <button type="button" data-date-nav="-1" aria-label="Previous month">‹</button>
                    <strong data-date-label></strong>
                    <button type="button" data-date-nav="1" aria-label="Next month">›</button>
                </div>
                <div class="people-date-week" aria-hidden="true"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div>
                <div class="people-date-grid" data-date-grid></div>
            </div>
            @push('script')
                <script>
                    (function () {
                        var modalEl = document.getElementById('applyLeaveModal');
                        var pop = document.getElementById('people-date-pop');
                        document.body.appendChild(pop);
                        var label = pop.querySelector('[data-date-label]');
                        var grid = pop.querySelector('[data-date-grid]');
                        var fields = [document.getElementById('starts_on'), document.getElementById('ends_on')];
                        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        var view = new Date();
                        var active = null;

                        function iso(year, month, day) {
                            return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        }

                        function place() {
                            var rect = active.getBoundingClientRect();
                            pop.hidden = false;
                            var top = rect.bottom + 6;
                            if (top + pop.offsetHeight > window.innerHeight - 8) {
                                top = Math.max(8, rect.top - pop.offsetHeight - 6);
                            }
                            pop.style.top = top + 'px';
                            pop.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - pop.offsetWidth - 8)) + 'px';
                        }

                        function render() {
                            var year = view.getFullYear();
                            var month = view.getMonth();
                            label.textContent = months[month] + ' ' + year;
                            grid.replaceChildren();
                            var start = (new Date(year, month, 1).getDay() + 6) % 7;
                            var days = new Date(year, month + 1, 0).getDate();
                            var selected = active && active.value;
                            var today = iso(new Date().getFullYear(), new Date().getMonth(), new Date().getDate());
                            for (var i = 0; i < start; i++) {
                                var blank = document.createElement('span');
                                grid.appendChild(blank);
                            }
                            for (var day = 1; day <= days; day++) {
                                var button = document.createElement('button');
                                var value = iso(year, month, day);
                                button.type = 'button';
                                button.textContent = String(day);
                                button.dataset.value = value;
                                if (value === selected) button.className = 'is-selected';
                                if (value === today) button.classList.add('is-today');
                                grid.appendChild(button);
                            }
                        }

                        function open(input) {
                            active = input;
                            var parsed = input.value ? new Date(input.value + 'T00:00:00') : new Date();
                            if (isNaN(parsed.getTime())) parsed = new Date();
                            view = new Date(parsed.getFullYear(), parsed.getMonth(), 1);
                            render();
                            place();
                        }

                        fields.forEach(function (input) {
                            if (!input) return;
                            input.addEventListener('click', function () {
                                if (active === input && !pop.hidden) {
                                    pop.hidden = true;
                                    active = null;
                                    return;
                                }
                                open(input);
                            });
                        });

                        pop.addEventListener('click', function (event) {
                            var nav = event.target.closest('[data-date-nav]');
                            if (nav) {
                                view = new Date(view.getFullYear(), view.getMonth() + Number(nav.dataset.dateNav), 1);
                                render();
                                place();
                                return;
                            }
                            var day = event.target.closest('[data-value]');
                            if (!day || !active) return;
                            active.value = day.dataset.value;
                            pop.hidden = true;
                            active = null;
                        });

                        document.addEventListener('mousedown', function (event) {
                            if (pop.hidden) return;
                            if (pop.contains(event.target) || fields.indexOf(event.target) !== -1) return;
                            pop.hidden = true;
                            active = null;
                        });

                        if (modalEl) {
                            modalEl.addEventListener('hidden.bs.modal', function () {
                                pop.hidden = true;
                                active = null;
                            });
                        }

                        @if($applyOpen)
                        if (modalEl && window.bootstrap) {
                            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                        }
                        @endif

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
                    })();
                </script>
            @endpush
            <article class="people-ws-card people-ws-mt">
                <h2>This week’s timesheet</h2>
                <p class="people-ws-note">Week of {{ $timesheet->week_starts_on->format('j F') }} · {{ $workspace->statusLabel($timesheet->status) }}</p>
                <div class="people-ws-week people-ws-mt">
                    @foreach($dayLabels as $key => $label)
                        <div class="people-ws-day"><strong>{{ $label }}</strong><span>{{ number_format((float) ($hours[$key] ?? 0), 1) }} hours</span></div>
                    @endforeach
                </div>
            </article>
        @endif

        @if($section === 'details')
            <div class="people-ws-head"><div><h1>My details</h1><p>Phone and address can be updated here. The rest is kept by HR.</p></div></div>
            <form class="people-ws-card" method="post" action="{{ route('admin.people.details') }}">
                @csrf
                <div class="people-ws-form-grid">
                    <div class="field"><label>Full name</label><input value="{{ $name }}" readonly></div>
                    <div class="field"><label>Employee code</label><input value="{{ $profile->employee_code }}" readonly></div>
                    <div class="field"><label>Seat</label><input value="{{ $profile->job_title }}" readonly></div>
                    <div class="field"><label>Manager</label><input value="{{ $profile->manager ? $workspace->displayName($profile->manager) : 'Not set' }}" readonly></div>
                    <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $actor->phone) }}" required></div>
                    <div class="field"><label>Email</label><input value="{{ $actor->email }}" readonly></div>
                    <div class="field"><label>Date of joining</label><input value="{{ $profile->joined_on ? $profile->joined_on->format('j F Y') : 'Not set' }}" readonly></div>
                    <div class="field"><label>Work location</label><input value="{{ $profile->work_location ?: 'Not set' }}" readonly></div>
                    <div class="field" style="grid-column:1/-1"><label for="address">Address</label><input id="address" name="address" value="{{ old('address', $profile->address) }}"></div>
                </div>
                <button class="btn-pw primary" type="submit">Save my details</button>
            </form>
        @endif

        @if($section === 'documents')
            <div class="people-ws-head"><div><h1>Documents</h1><p>Upload what HR asked for. You can download anything already on your file.</p></div></div>
            <div class="people-ws-card">
                <form method="post" action="{{ route('admin.people.documents.store') }}" enctype="multipart/form-data" class="people-ws-form-grid">
                    @csrf
                    <div class="field"><label for="title">Document</label>
                        <select id="title" name="title" required>
                            @foreach($documents as $document)
                                <option value="{{ $document->title }}">{{ $document->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="file">File</label><input id="file" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required></div>
                    <div><button class="btn-pw primary" type="submit">Upload</button></div>
                </form>
                <div class="people-ws-scroll people-ws-mt">
                    <table>
                        <thead><tr><th>Document</th><th>Uploaded</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($documents as $document)
                            <tr>
                                <td>{{ $document->title }}</td>
                                <td>{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</td>
                                <td>@include('adminmodule::admin.people._badge', ['status' => $document->status])</td>
                                <td>@if($document->file_path)<a class="btn-pw" href="{{ route('admin.people.documents.download', $document) }}">Download</a>@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($section === 'payslips')
            <div class="people-ws-head"><div><h1>Payslips</h1><p>Only slips HR has published. You cannot see anyone else’s pay.</p></div></div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Month</th><th>Paid on</th><th>Net pay</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($payslips as $payslip)
                        <tr>
                            <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $payslip->period)->format('F Y') }}</td>
                            <td>{{ $payslip->published_at ? $payslip->published_at->format('j M Y') : '—' }}</td>
                            <td>{{ $payslip->status === 'published' ? '₹'.number_format((float) $payslip->net, 0) : '—' }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $payslip->status])</td>
                            <td>
                                @if($payslip->status === 'published')
                                    <a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Download</a>
                                @else
                                    <button class="btn-pw" type="button" disabled>Download</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="people-ws-note">No payslips yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
        @endif

        @if($section === 'timesheet')
            @include('adminmodule::admin.people._timesheet')
        @endif

        @include('adminmodule::admin.people._close')
    </div>
</div>
@endsection
