@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $net = $payslips->where('held', false)->sum('net');
    $locked = $run && $run->status === 'locked';
@endphp
<div class="people-ws-head">
    <div>
        <h1>{{ $monthLabel }} payroll</h1>
        <p>{{ $run ? $workspace->statusLabel($run->status) : 'Not built' }}{{ $run && $run->published_at ? ' · published '.$run->published_at->format('j M Y') : '' }}. Held people stay off the bank file.</p>
    </div>
    <div class="people-ws-head-actions">
        <a class="btn-pw" href="{{ route('admin.hr.bank', ['period' => $period]) }}">Bank file</a>
        @unless($locked)
            <form method="post" action="{{ route('admin.hr.payroll.publish') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn-pw primary" type="submit" @disabled(! $run || $payslips->where('status', 'draft')->where('held', false)->isEmpty())>Publish</button>
            </form>
        @endunless
        @if($run && $run->status === 'published')
            <form method="post" action="{{ route('admin.hr.payroll.lock') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button class="btn-pw" type="submit">Lock month</button>
            </form>
        @endif
    </div>
</div>
<form class="people-ws-card" method="get" action="{{ route('admin.hr.index') }}">
    <input type="hidden" name="section" value="payroll">
    <div class="field"><label for="payroll_period">Month</label><input id="payroll_period" type="month" name="period" value="{{ $period }}" onchange="this.form.submit()"></div>
</form>
@unless($locked || ($run && $run->status === 'published'))
    <form class="people-ws-card people-ws-mt" method="post" action="{{ route('admin.hr.payroll.build') }}">
        @csrf
        <input type="hidden" name="period" value="{{ $period }}">
        <label><input type="checkbox" name="count_missing_weeks" value="1"> Count weeks that were never approved as loss of pay</label>
        <button class="btn-pw primary" type="submit">Build draft</button>
    </form>
@endunless
<article class="people-ws-card people-ws-mt people-ws-scroll">
    <p class="people-ws-note">Net in this draft, excluding holds: ₹{{ number_format((float) $net, 0) }}</p>
    <table>
        <thead><tr><th>Person</th><th>Loss of pay</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($payslips as $payslip)
            <tr>
                <td>{{ $payslip->user ? $workspace->displayName($payslip->user) : '—' }}@if(!empty($payslip->breakdown['missing_structure']))<div class="people-ws-note">No salary structure</div>@endif</td>
                <td>{{ rtrim(rtrim(number_format((float) $payslip->lop_days, 1), '0'), '.') }} days</td>
                <td>₹{{ number_format((float) $payslip->gross, 0) }}</td>
                <td>₹{{ number_format((float) $payslip->deductions, 0) }}</td>
                <td>₹{{ number_format((float) $payslip->net, 0) }}</td>
                <td>@include('adminmodule::admin.people._badge', ['status' => $payslip->held ? 'held' : $payslip->status])</td>
                <td class="people-ws-actions">
                    @if($payslip->status === 'published')
                        <a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Slip</a>
                    @endif
                    @if($payslip->status === 'draft' && ! $locked)
                        <form method="post" action="{{ route('admin.hr.payroll.hold', $payslip) }}">
                            @csrf
                            <button class="btn-pw" type="submit">{{ $payslip->held ? 'Release' : 'Hold' }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="people-ws-note">Build a draft to see this month.</td></tr>
        @endforelse
        </tbody>
    </table>
</article>
