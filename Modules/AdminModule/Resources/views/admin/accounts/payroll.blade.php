@extends('adminmodule::layouts.new-master')

@section('title', 'Payroll totals')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="people-ws-head">
            <div>
                <h1>{{ $monthLabel }} payroll totals</h1>
                <p>Published pay only. Bank, PAN, Aadhaar, and the employee file stay with HR.</p>
            </div>
        </div>
        <form class="people-ws-card" method="get" action="{{ route('admin.accounts.payroll') }}">
            <div class="field">
                <label for="payroll_period">Month</label>
                <input id="payroll_period" type="month" name="period" value="{{ $period }}" onchange="this.form.submit()">
            </div>
        </form>
        <div class="people-ws-grid-4 people-ws-mt">
            <article class="people-ws-card people-ws-stat"><div class="label">People</div><div class="value">{{ $headcount }}</div><div class="sub">Published slips</div></article>
            <article class="people-ws-card people-ws-stat"><div class="label">Gross</div><div class="value">₹{{ number_format($gross, 0) }}</div></article>
            <article class="people-ws-card people-ws-stat"><div class="label">Deductions</div><div class="value">₹{{ number_format($deductions, 0) }}</div></article>
            <article class="people-ws-card people-ws-stat"><div class="label">Net</div><div class="value">₹{{ number_format($net, 0) }}</div></article>
        </div>
        <article class="people-ws-card people-ws-mt people-ws-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($slips as $slip)
                    <tr>
                        <td>{{ $slip->user ? $workspace->displayName($slip->user) : '—' }}</td>
                        <td>₹{{ number_format((float) $slip->gross, 0) }}</td>
                        <td>₹{{ number_format((float) $slip->deductions, 0) }}</td>
                        <td>₹{{ number_format((float) $slip->net, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="people-ws-note">No published payroll for {{ $monthLabel }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </article>
    </div>
</div>
@endsection
