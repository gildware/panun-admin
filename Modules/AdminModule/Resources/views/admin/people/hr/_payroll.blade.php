@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $locked = $run && $run->status === 'locked';
    $rows = $payslips->sortBy(fn ($slip) => $slip->user ? $workspace->displayName($slip->user) : '')->values();
    $paying = $rows->filter(fn ($slip) => ! $slip->held && empty($slip->breakdown['missing_structure']));
    $salaryPayable = (float) $paying->sum('gross');
    $deductionTotal = (float) $paying->sum('deductions');
    $netPayable = (float) $paying->sum('net');
    $money = fn ($amount) => '₹'.number_format((float) $amount, 0);
    $days = fn ($amount) => rtrim(rtrim(number_format((float) $amount, 1), '0'), '.');
@endphp
<div class="people-pay-top">
    <div class="people-pay-top-main">
        <div class="people-pay-title">
            <h1>{{ $monthLabel }} payroll</h1>
            <p>{{ $workspace->payrollRunLabel($run->status ?? null) }}{{ $run && $run->published_at ? ' · '.$run->published_at->format('j M Y') : '' }}</p>
        </div>
        <form method="get" action="{{ route('admin.accounts.payroll') }}">
            <input id="payroll_period" type="month" name="period" value="{{ $period }}" aria-label="Month" onchange="this.form.submit()">
        </form>
        @unless($locked || ($run && $run->status === 'published'))
            <form class="people-pay-calc" method="post" action="{{ route('admin.hr.payroll.build') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <label class="people-pay-check"><input type="checkbox" name="count_missing_days" value="1"> Count each missed day as loss of pay</label>
                <button class="btn-pw primary" type="submit">Calculate pay</button>
            </form>
        @endunless
        <div class="people-ws-head-actions">
            <a class="btn-pw" href="{{ route('admin.hr.payroll.net', ['period' => $period]) }}">Download net pay</a>
            @unless($locked)
                <form method="post" action="{{ route('admin.hr.payroll.publish') }}">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button class="btn-pw primary" type="submit" title="Send payslips for everyone who will be paid" @disabled($paying->where('status', 'draft')->isEmpty())>Send payslips</button>
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
    <div class="people-pay-figures">
        <div><span>According to salary</span><strong>{{ $money($salaryPayable) }}</strong></div>
        <div><span>Deductions</span><strong>{{ $money($deductionTotal) }}</strong></div>
        <div class="is-net"><span>Net payable</span><strong>{{ $money($netPayable) }}</strong><em>{{ $paying->count() }} {{ $paying->count() === 1 ? 'person' : 'people' }}</em></div>
    </div>
</div>
<article class="people-ws-card people-ws-mt people-ws-scroll">
    <table class="people-pay-table">
        <thead>
            <tr>
                <th>Person</th>
                <th class="is-money">Loss of pay</th>
                <th class="is-money">According to salary</th>
                <th class="is-money">Deductions</th>
                <th class="is-money">Net payable</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $payslip)
            @php
                $noSalary = ! empty($payslip->breakdown['missing_structure']);
                $included = ! $payslip->held && ! $noSalary;
            @endphp
            <tr class="{{ $noSalary ? 'is-none' : ($included ? '' : 'is-out') }}">
                <td>{{ $payslip->user ? $workspace->displayName($payslip->user) : '—' }}@if($noSalary)<div class="people-ws-note">No salary saved</div>@endif</td>
                <td class="is-money">{{ $noSalary ? '—' : $days($payslip->lop_days).' days' }}</td>
                <td class="is-money">{{ $noSalary ? '—' : $money($payslip->gross) }}</td>
                <td class="is-money">{{ $noSalary ? '—' : $money($payslip->deductions) }}</td>
                <td class="is-money is-net">{{ $noSalary ? '—' : $money($payslip->net) }}</td>
                <td>@include('adminmodule::admin.people._badge', ['status' => $noSalary ? 'missing' : ($payslip->held ? 'held' : $payslip->status), 'label' => $noSalary ? 'No salary' : $workspace->payrollStatusLabel($payslip->status, (bool) $payslip->held)])</td>
                <td class="people-ws-actions">
                    @if($payslip->status === 'published' && ! $noSalary)
                        <a class="btn-pw" href="{{ route('admin.people.payslips.download', $payslip) }}">Slip</a>
                    @endif
                    @if(! $noSalary && $payslip->status === 'draft' && ! $locked)
                        <form method="post" action="{{ route('admin.hr.payroll.hold', $payslip) }}">
                            @csrf
                            <button class="btn-pw" type="submit" title="{{ $payslip->held ? 'Include this person in this month’s pay' : 'Leave this person out of this month’s pay' }}">{{ $payslip->held ? 'Put back' : 'Leave out' }}</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="people-ws-note">Calculate pay to see this month.</td></tr>
        @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Will be paid</td>
                    <td class="is-money">{{ $days($paying->sum('lop_days')) }} days</td>
                    <td class="is-money">{{ $money($salaryPayable) }}</td>
                    <td class="is-money">{{ $money($deductionTotal) }}</td>
                    <td class="is-money is-net">{{ $money($netPayable) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</article>
