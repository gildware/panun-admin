@extends('adminmodule::layouts.new-master')

@section('title', 'My workspace')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
@php
    $name = $workspace->displayName($actor);
@endphp
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'mode' => 'mine',
            'section' => $section,
            'baseUrl' => route('admin.people.index'),
            'links' => [],
        ])

        @php
            $documentsOpen = $section === 'documents' || (old('form_context') === 'mine-document' && $errors->any());
            $tab = $section === 'documents' ? 'profile' : (in_array($section, ['profile', 'payslips', 'timesheet'], true) ? $section : 'home');
            $basicErrors = old('form_context') === 'mine-basic' && $errors->any();
            $operationErrors = old('form_context') === 'mine-operation' && $errors->any();
            $savedStatus = $profile->employment_status ?: 'active';
            $statusValue = old('employment_status', $savedStatus);
            $managerName = $profile->manager ? $workspace->displayName($profile->manager) : null;
            $mineTabs = [
                'profile' => 'Profile',
                'timesheet' => 'Timesheet',
                'home' => 'Leaves',
                'payslips' => 'Payslips',
            ];
            $profileDocuments = $documents->filter(fn ($document) => filled($document->file_path));
        @endphp
        <div class="people-file people-file--stack">
            <div class="people-file-toolbar">
                <div class="people-file-lead">
                    <span class="people-file-name">{{ $name }}</span>
                </div>
                <nav class="people-ws-tabs" aria-label="My workspace">
                    @foreach($mineTabs as $key => $label)
                        <a class="{{ $tab === $key ? 'is-on' : '' }}" href="{{ route('admin.people.index', ['section' => $key]) }}">{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="people-file-main">

        @if($tab === 'profile')
            <div class="ep-board ep-board--pair">
                <article class="ep-card">
                    <header class="ep-card-head">
                        <h3>Basic details</h3>
                        <button class="ep-edit" type="button" id="mine-basic-edit" @if($basicErrors) hidden @endif><span class="material-icons" aria-hidden="true">edit</span> Edit</button>
                    </header>
                    <div id="mine-basic-view" @if($basicErrors) hidden @endif>
                        <div class="ep-person">
                            <img class="ep-photo avatar-img" src="{{ admin_nav_image_src($actor->profile_image_full_path, 'profile') }}" alt="{{ $name }}">
                            <div>
                                <h2>{{ $name }}</h2>
                                <span class="ep-status is-{{ $savedStatus }}">{{ $workspace->statusLabel($savedStatus) }}</span>
                            </div>
                        </div>
                        <dl class="ep-facts">
                            <div class="ep-fact">
                                <dt>Email</dt>
                                <dd class="{{ $actor->email ? '' : 'is-empty' }}">{{ $actor->email ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Phone</dt>
                                <dd class="{{ $actor->phone ? '' : 'is-empty' }}">{{ $actor->phone ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Date of birth</dt>
                                <dd class="{{ $profile->date_of_birth ? '' : 'is-empty' }}">{{ $profile->date_of_birth ? $profile->date_of_birth->format('j M Y') : '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Emergency contact</dt>
                                <dd class="{{ $profile->emergency_contact ? '' : 'is-empty' }}">{{ $profile->emergency_contact ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact is-block">
                                <dt>Address</dt>
                                <dd class="{{ $profile->address ? '' : 'is-empty' }}">{{ $profile->address ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <form class="ep-form" id="mine-basic-form" method="post" action="{{ route('admin.people.details') }}" @if(! $basicErrors) hidden @endif>
                        @csrf
                        <input type="hidden" name="form_context" value="mine-basic">
                        <div class="ep-person">
                            <img class="ep-photo avatar-img" src="{{ admin_nav_image_src($actor->profile_image_full_path, 'profile') }}" alt="">
                            <h2>{{ $name }}</h2>
                        </div>
                        <div class="ep-form-grid">
                            <div class="field span-2">
                                <label for="full_name">Full name</label>
                                <input id="full_name" name="full_name" value="{{ old('full_name', $name) }}" required maxlength="120">
                                @error('full_name')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field span-2">
                                <label for="email">Email</label>
                                <input id="email" value="{{ $actor->email }}" readonly>
                            </div>
                            <div class="field">
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" value="{{ old('phone', $actor->phone) }}" required maxlength="30">
                                @error('phone')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="employment_status">Status</label>
                                <select id="employment_status" name="employment_status">
                                    @foreach(['active' => 'Active', 'notice' => 'On notice', 'exited' => 'Exited'] as $value => $label)
                                        <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('employment_status')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="date_of_birth">Date of birth</label>
                                <input id="date_of_birth" class="people-date-field" name="date_of_birth" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('date_of_birth', optional($profile->date_of_birth)->toDateString()) }}" readonly>
                                @error('date_of_birth')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="emergency_contact">Emergency contact</label>
                                <input id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact', $profile->emergency_contact) }}" maxlength="120" placeholder="Name and phone">
                                @error('emergency_contact')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field span-2">
                                <label for="address">Address</label>
                                <input id="address" name="address" value="{{ old('address', $profile->address) }}" maxlength="500">
                                @error('address')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="ep-form-actions">
                            <button class="btn-pw" type="button" data-cancel>Cancel</button>
                            <button class="btn-pw primary" type="submit">Save</button>
                        </div>
                    </form>
                </article>
                <article class="ep-card">
                    <header class="ep-card-head">
                        <h3>Operation details</h3>
                        <button class="ep-edit" type="button" id="mine-operation-edit" @if($operationErrors) hidden @endif><span class="material-icons" aria-hidden="true">edit</span> Edit</button>
                    </header>
                    <p class="ep-id"><span>Employee ID</span>{{ \Modules\AdminModule\Services\PeopleWorkspace::formatCode($profile->employee_code) }}</p>
                    <div id="mine-operation-view" @if($operationErrors) hidden @endif>
                        <div class="ep-roles">
                            @forelse($actor->roles as $role)
                                <span class="ep-pill">{{ $role->role_name }}</span>
                            @empty
                                <span class="ep-muted">No role</span>
                            @endforelse
                        </div>
                        <dl class="ep-facts">
                            <div class="ep-fact">
                                <dt>Seat</dt>
                                <dd class="{{ $profile->job_title ? '' : 'is-empty' }}">{{ $profile->job_title ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Type</dt>
                                <dd>{{ $profile->employment_type === 'contract' ? 'Contract' : 'Full time' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Department</dt>
                                <dd class="{{ $profile->department ? '' : 'is-empty' }}">{{ $profile->department ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Manager</dt>
                                <dd class="{{ $managerName ? '' : 'is-empty' }}">{{ $managerName ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Work location</dt>
                                <dd class="{{ $profile->work_location ? '' : 'is-empty' }}">{{ $profile->work_location ?: '—' }}</dd>
                            </div>
                            <div class="ep-fact">
                                <dt>Date of joining</dt>
                                <dd class="{{ $profile->joined_on ? '' : 'is-empty' }}">{{ $profile->joined_on ? $profile->joined_on->format('j M Y') : '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <form class="ep-form" id="mine-operation-form" method="post" action="{{ route('admin.people.details') }}" @if(! $operationErrors) hidden @endif>
                        @csrf
                        <input type="hidden" name="form_context" value="mine-operation">
                        <div class="ep-form-grid">
                            <div class="field">
                                <label for="job_title">Seat</label>
                                <input id="job_title" name="job_title" value="{{ old('job_title', $profile->job_title) }}" required maxlength="120">
                                @error('job_title')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="employment_type">Type</label>
                                <select id="employment_type" name="employment_type">
                                    <option value="full_time" @selected(old('employment_type', $profile->employment_type ?: 'full_time') === 'full_time')>Full time</option>
                                    <option value="contract" @selected(old('employment_type', $profile->employment_type) === 'contract')>Contract</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="department">Department</label>
                                <select id="department" name="department">
                                    <option value="">Not set</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->name }}" @selected(old('department', $profile->department) === $department->name)>{{ $department->name }}</option>
                                    @endforeach
                                    @if($profile->department && ! $departments->contains('name', $profile->department))
                                        <option value="{{ $profile->department }}" @selected(old('department', $profile->department) === $profile->department)>{{ $profile->department }}</option>
                                    @endif
                                </select>
                                @error('department')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="manager_id">Manager</label>
                                <select id="manager_id" name="manager_id">
                                    <option value="">Not set</option>
                                    @foreach($colleagues as $colleague)
                                        @if((string) $colleague->id !== (string) $actor->id)
                                            <option value="{{ $colleague->id }}" @selected((string) old('manager_id', $profile->manager_id) === (string) $colleague->id)>{{ $workspace->displayName($colleague) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('manager_id')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="work_location">Work location</label>
                                <input id="work_location" name="work_location" value="{{ old('work_location', $profile->work_location) }}" maxlength="120">
                                @error('work_location')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="field">
                                <label for="joined_on">Date of joining</label>
                                <input id="joined_on" class="people-date-field" name="joined_on" type="text" inputmode="none" autocomplete="off" placeholder="Select a date" value="{{ old('joined_on', optional($profile->joined_on)->toDateString()) }}" readonly>
                                @error('joined_on')<p class="ep-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="ep-form-actions">
                            <button class="btn-pw" type="button" data-cancel>Cancel</button>
                            <button class="btn-pw primary" type="submit">Save</button>
                        </div>
                    </form>
                </article>
            </div>
            <script>
                (function () {
                    var sections = [
                        ['mine-basic-view', 'mine-basic-form', 'mine-basic-edit'],
                        ['mine-operation-view', 'mine-operation-form', 'mine-operation-edit']
                    ].map(function (ids) {
                        return {
                            view: document.getElementById(ids[0]),
                            form: document.getElementById(ids[1]),
                            edit: document.getElementById(ids[2])
                        };
                    });
                    function show(section, on) {
                        if (section.view) section.view.hidden = on;
                        if (section.form) section.form.hidden = !on;
                        if (section.edit) section.edit.hidden = on;
                        if (on) {
                            sections.forEach(function (other) {
                                if (other !== section) show(other, false);
                            });
                        }
                    }
                    sections.forEach(function (section) {
                        if (section.edit) section.edit.addEventListener('click', function () { show(section, true); });
                        var cancel = section.form ? section.form.querySelector('[data-cancel]') : null;
                        if (cancel) cancel.addEventListener('click', function () { show(section, false); });
                    });
                })();
            </script>
            <article class="ep-card ep-docs">
                <header class="ep-card-head">
                    <h3>Documents</h3>
                    <button class="ep-edit" type="button" id="mine-documents-view-all" onclick="var all=document.getElementById('mine-documents-all'); if(!all) return; all.hidden=!all.hidden; this.textContent=all.hidden?'View all':'Hide'; if(!all.hidden) all.scrollIntoView({behavior:'smooth', block:'nearest'});">{{ $documentsOpen ? 'Hide' : 'View all' }}</button>
                </header>
                @if($profileDocuments->isEmpty())
                    <div class="ep-empty">
                        <span class="material-icons" aria-hidden="true">folder_open</span>
                        <div>
                            <strong>No documents yet</strong>
                            <p>Offer letters, IDs, and other files will show up here.</p>
                        </div>
                    </div>
                @else
                    <ul class="ep-doc-list">
                        @foreach($profileDocuments->take(3) as $document)
                            <li>
                                <span class="material-icons" aria-hidden="true">description</span>
                                <span class="ep-doc-name">{{ $document->title }}</span>
                                <span class="ep-doc-date">{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
            <div id="mine-documents-all" @if(! $documentsOpen) hidden @endif>
                <p class="people-ws-note">Upload what HR asked for. You can download anything already on your file.</p>
                <div class="people-ws-card">
                    <form method="post" action="{{ route('admin.people.documents.store') }}" enctype="multipart/form-data" class="people-ws-form-grid">
                        @csrf
                        <input type="hidden" name="form_context" value="mine-document">
                        <div class="field"><label for="title">Document</label>
                            <select id="title" name="title" required>
                                @foreach($documents as $document)
                                    <option value="{{ $document->title }}" @selected(old('title') === $document->title)>{{ $document->title }}</option>
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
            </div>
        @endif

        @if($tab === 'home')
            @php
                $leaveShort = $leaveTypes->mapWithKeys(fn ($type) => [$type->code => $type->short_name ?: strtoupper(substr($type->code, 0, 3))])->all();
                $leaveNames = $leaveTypes->mapWithKeys(fn ($type) => [$type->code => $type->name])->all();
                $pad = function ($n): string {
                    $n = (float) $n;

                    return abs($n - round($n)) < 0.001 ? sprintf('%02d', (int) round($n)) : number_format($n, 1);
                };
                $totalAvailable = 0;
                $totalUsed = 0;
                foreach ($leaveTypes->where('tracks_balance', true) as $type) {
                    $totalAvailable += $balance->remaining($type->code);
                    $totalUsed += $balance->taken($type->code);
                }
            @endphp
            <div class="people-lh">
                <article class="people-lh-total">
                    <h2>Total Leaves</h2>
                    <div class="people-lh-fill people-lh-balance-wrap">
                        <table class="people-lh-balance">
                            <thead>
                                <tr>
                                    <th>Leave type</th>
                                    <th class="is-num">Available</th>
                                    <th class="is-num">Used</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="is-total">
                                    <td>Total</td>
                                    <td class="is-num">{{ $pad($totalAvailable) }}</td>
                                    <td class="is-num">{{ $pad($totalUsed) }}</td>
                                </tr>
                                @foreach($leaveTypes as $type)
                                    <tr>
                                        <td title="{{ $type->name }}">
                                            @if($type->short_name)
                                                <span class="people-lh-code">{{ $type->short_name }}</span>
                                            @endif
                                            <span class="people-lh-name">{{ $type->name }}</span>
                                        </td>
                                        @if($type->tracks_balance)
                                            <td class="is-num">{{ $pad($balance->remaining($type->code)) }}</td>
                                            <td class="is-num">{{ $pad($balance->taken($type->code)) }}</td>
                                        @else
                                            <td class="is-num">—</td>
                                            <td class="is-num">—</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
                <article class="people-lh-status">
                    <div class="people-lh-status-bar">
                        <h2>Leave Status</h2>
                        <button class="people-lh-apply" type="button" data-bs-toggle="modal" data-bs-target="#applyLeaveModal">Apply leave</button>
                    </div>
                    <div class="people-lh-fill people-lh-table-wrap">
                        @if($leaveRequests->isEmpty())
                            <p class="people-ws-note">No leave requests yet.</p>
                        @else
                            <table class="people-lh-table">
                                <colgroup>
                                    <col class="is-start">
                                    <col class="is-end">
                                    <col class="is-reason">
                                    <col class="is-type">
                                    <col class="is-status">
                                    <col class="is-action">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Reason</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($leaveRequests as $leave)
                                    @php $leaveName = $leaveNames[$leave->leave_type] ?? $workspace->leaveLabel($leave->leave_type); @endphp
                                    <tr>
                                        <td title="{{ $leave->starts_on->format('j M Y') }}">{{ $leave->starts_on->format('j M Y') }}</td>
                                        <td title="{{ $leave->ends_on->format('j M Y') }}">{{ $leave->ends_on->format('j M Y') }}</td>
                                        <td title="{{ $leave->reason }}{{ $leave->status === 'sent_back' && filled($leave->decision_note) ? ' — Rejected: '.$leave->decision_note : '' }}">{{ $leave->reason }}</td>
                                        <td title="{{ $leaveName }}">{{ $leaveName }}</td>
                                        <td><span class="people-lh-pill is-{{ $leave->status }}">{{ $workspace->statusLabel($leave->status) }}</span></td>
                                        <td>
                                            @if($leave->status === 'pending')
                                                <button
                                                    class="people-lh-revoke"
                                                    type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#revokeLeaveModal"
                                                    data-action="{{ route('admin.people.leave.cancel', $leave) }}"
                                                    data-summary="{{ $leaveName }} · {{ $leave->starts_on->format('j M Y') }} – {{ $leave->ends_on->format('j M Y') }}"
                                                >Revoke</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </article>
            </div>
            @include('adminmodule::admin.people._leave_calendar', ['leaveCalendar' => $workspace->leaveCalendar($actor->id)])
            @include('adminmodule::admin.people._leave_history', ['leaveHistory' => $leaveHistory ?? []])
            @include('adminmodule::admin.people._apply_leave_modal', ['returnSection' => 'home'])
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
            @push('script')
                <script>
                    (function () {
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
        @endif

        @if($tab === 'payslips')
            <p class="people-ws-note">Only slips HR has published.</p>
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

        @if($tab === 'timesheet')
            @include('adminmodule::admin.people._timesheet')
            @include('adminmodule::admin.people._apply_leave_modal', ['returnSection' => 'timesheet'])
        @endif
        @include('adminmodule::admin.people._date_pop')
            </div>
        </div>

        @include('adminmodule::admin.people._close')
    </div>
</div>
@endsection
