@extends('adminmodule::layouts.new-master')

@section('title', 'People records')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $draftCount = $payslips->where('status', 'draft')->count();
@endphp
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'mode' => 'records',
            'section' => $section,
            'baseUrl' => route('admin.people.records'),
            'links' => [
                'people' => 'People',
                'leave' => 'Leave balances',
                'documents' => 'Documents',
                'payslips' => 'Payslips',
            ],
        ])

        @if($section === 'people')
            <div class="people-ws-head"><div><h1>People files</h1><p>One file for each person who signs in to this admin panel. Providers are not in this list.</p></div></div>
            <div class="people-ws-grid-4">
                <article class="people-ws-card people-ws-stat"><div class="label">Employees</div><div class="value">{{ $staff->count() }}</div><div class="sub">Active files</div></article>
                <article class="people-ws-card people-ws-stat"><div class="label">Documents missing</div><div class="value">{{ $missingDocuments }}</div><div class="sub">Asked, not uploaded</div></article>
                <article class="people-ws-card people-ws-stat"><div class="label">Leave waiting</div><div class="value">{{ $pendingLeave }}</div><div class="sub">Not decided yet</div></article>
                <article class="people-ws-card people-ws-stat"><div class="label">{{ $monthLabel }} payslips</div><div class="value">{{ $draftCount }}</div><div class="sub">Still in draft</div></article>
            </div>
            <article class="people-ws-card people-ws-mt people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Code</th><th>Seat</th><th>Manager</th><th>Joined</th><th>File</th></tr></thead>
                    <tbody>
                    @foreach($staff as $person)
                        @php
                            $profile = $profiles->get($person->id);
                            $missing = $documents->where('user_id', $person->id)->where('status', 'missing')->count();
                        @endphp
                        <tr>
                            <td>{{ $workspace->displayName($person) }}</td>
                            <td>{{ $profile->employee_code ?? '—' }}</td>
                            <td>{{ $profile->job_title ?? 'Employee' }}</td>
                            <td>{{ $profile && $profile->manager ? $workspace->displayName($profile->manager) : 'Not set' }}</td>
                            <td>{{ $profile && $profile->joined_on ? $profile->joined_on->format('j M Y') : '—' }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $missing > 0 ? 'missing' : 'verified'])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </article>
            <form class="people-ws-card people-ws-mt" method="post" action="{{ route('admin.people.records.profile') }}">
                @csrf
                <h2>Update a file</h2>
                <div class="people-ws-form-grid">
                    <div class="field"><label for="user_id">Person</label>
                        <select id="user_id" name="user_id" required>
                            @foreach($staff as $person)
                                <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="job_title">Seat</label><input id="job_title" name="job_title" value="{{ old('job_title', 'Employee') }}" required></div>
                    <div class="field"><label for="work_location">Work location</label><input id="work_location" name="work_location" value="{{ old('work_location') }}"></div>
                    <div class="field"><label for="joined_on">Joined</label><input id="joined_on" type="date" name="joined_on" value="{{ old('joined_on') }}"></div>
                    <div class="field"><label for="manager_id">Manager</label>
                        <select id="manager_id" name="manager_id">
                            <option value="">Not set</option>
                            @foreach($staff as $person)
                                <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="address">Address</label><input id="address" name="address" value="{{ old('address') }}"></div>
                </div>
                <button class="btn-pw primary" type="submit">Save file</button>
            </form>
        @endif

        @if($section === 'leave')
            <div class="people-ws-head"><div><h1>Leave balances</h1><p>HR writes the allowance. A manager, or HR, approves the request and the used days update.</p></div></div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Casual left</th><th>Sick left</th><th>Earned left</th><th>Latest request</th></tr></thead>
                    <tbody>
                    @foreach($staff as $person)
                        @php
                            $balance = $balances->get($person->id);
                            $latest = $leaveRequests->firstWhere('user_id', $person->id);
                        @endphp
                        <tr>
                            <td>{{ $workspace->displayName($person) }}</td>
                            <td>{{ $balance ? $balance->remaining('casual').' of '.$balance->allowance('casual') : '—' }}</td>
                            <td>{{ $balance ? $balance->remaining('sick').' of '.$balance->allowance('sick') : '—' }}</td>
                            <td>{{ $balance ? $balance->remaining('earned').' of '.$balance->allowance('earned') : '—' }}</td>
                            <td>@if($latest){{ $workspace->leaveLabel($latest->leave_type) }} · @include('adminmodule::admin.people._badge', ['status' => $latest->status])@else<span class="people-ws-note">None</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </article>
            <article class="people-ws-card people-ws-mt people-ws-scroll">
                <h2>Open requests</h2>
                <table>
                    <thead><tr><th>Person</th><th>Type</th><th>Dates</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($leaveRequests->where('status', 'pending') as $leave)
                        <tr>
                            <td>{{ $workspace->displayName($leave->user) }}</td>
                            <td>{{ $workspace->leaveLabel($leave->leave_type) }}</td>
                            <td>{{ $leave->starts_on->format('j M') }}–{{ $leave->ends_on->format('j M') }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $leave->status])</td>
                            <td>
                                <form method="post" action="{{ route('admin.people.team.leave.decide', $leave) }}">
                                    @csrf
                                    <input type="hidden" name="return_to" value="records-leave">
                                    <button class="btn-pw good" name="decision" value="approve">Approve</button>
                                    <button class="btn-pw danger" name="decision" value="sent_back">Send back</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="people-ws-note">No leave is waiting.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
            <form class="people-ws-card people-ws-mt" method="post" action="{{ route('admin.people.records.allowances') }}">
                @csrf
                <h2>Set this year’s allowances</h2>
                <div class="people-ws-form-grid">
                    <div class="field"><label for="allowance_user">Person</label>
                        <select id="allowance_user" name="user_id" required>
                            @foreach($staff as $person)
                                <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="casual_allowance">Casual days</label><input id="casual_allowance" name="casual_allowance" type="number" min="0" value="8" required></div>
                    <div class="field"><label for="sick_allowance">Sick days</label><input id="sick_allowance" name="sick_allowance" type="number" min="0" value="12" required></div>
                    <div class="field"><label for="earned_allowance">Earned days</label><input id="earned_allowance" name="earned_allowance" type="number" min="0" value="15" required></div>
                </div>
                <button class="btn-pw primary" type="submit">Save allowances</button>
            </form>
        @endif

        @if($section === 'documents')
            <div class="people-ws-head"><div><h1>Documents to check</h1><p>Mark a file as on record after you have opened it.</p></div></div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Document</th><th>Received</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($documents as $document)
                        <tr>
                            <td>{{ $document->user ? $workspace->displayName($document->user) : '—' }}</td>
                            <td>{{ $document->title }}</td>
                            <td>{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $document->status])</td>
                            <td class="people-ws-actions">
                                @if($document->file_path)
                                    <a class="btn-pw" href="{{ route('admin.people.documents.download', $document) }}">Download</a>
                                @endif
                                @if($document->status === 'pending')
                                    <form method="post" action="{{ route('admin.people.records.documents.verify', $document) }}">
                                        @csrf
                                        <button class="btn-pw" type="submit">Mark on file</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </article>
        @endif

        @if($section === 'payslips')
            <div class="people-ws-head">
                <div>
                    <h1>{{ $monthLabel }} payslips</h1>
                    <p>A slip stays hidden from the employee until you publish that month.</p>
                </div>
                <form method="post" action="{{ route('admin.people.records.payslips.publish') }}">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button class="btn-pw primary" type="submit" @disabled($draftCount === 0)>Publish {{ $monthLabel }}</button>
                </form>
            </div>
            <article class="people-ws-card people-ws-scroll">
                <table>
                    <thead><tr><th>Person</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($payslips as $payslip)
                        <tr>
                            <td>{{ $payslip->user ? $workspace->displayName($payslip->user) : '—' }}</td>
                            <td>₹{{ number_format((float) $payslip->gross, 0) }}</td>
                            <td>₹{{ number_format((float) $payslip->deductions, 0) }}</td>
                            <td>₹{{ number_format((float) $payslip->net, 0) }}</td>
                            <td>@include('adminmodule::admin.people._badge', ['status' => $payslip->status])</td>
                            <td><a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Download</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="people-ws-note">No payslips for {{ $monthLabel }} yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </article>
            <form class="people-ws-card people-ws-mt" method="post" action="{{ route('admin.people.records.payslips.store') }}">
                @csrf
                <h2>Add a draft</h2>
                <div class="people-ws-form-grid">
                    <div class="field"><label for="payslip_user">Person</label>
                        <select id="payslip_user" name="user_id" required>
                            @foreach($staff as $person)
                                <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label for="period">Month</label><input id="period" type="month" name="period" value="{{ old('period', $period) }}" required></div>
                    <div class="field"><label for="gross">Gross</label><input id="gross" name="gross" type="number" min="0" step="0.01" required></div>
                    <div class="field"><label for="deductions">Deductions</label><input id="deductions" name="deductions" type="number" min="0" step="0.01" value="0" required></div>
                </div>
                <button class="btn-pw primary" type="submit">Save draft</button>
            </form>
        @endif

        @include('adminmodule::admin.people._close')
    </div>
</div>
@endsection
