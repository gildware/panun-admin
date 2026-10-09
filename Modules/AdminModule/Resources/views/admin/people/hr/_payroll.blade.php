@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $period)->format('F Y');
    $locked = $run && $run->status === 'locked';
    $rows = $payslips->sortBy(fn ($slip) => $slip->user ? $workspace->displayName($slip->user) : '')->values();
    $paying = $rows->filter(fn ($slip) => ! $slip->held && empty($slip->breakdown['missing_structure']));
    $baseSalaries = app(\Modules\AdminModule\Services\PeoplePayroll::class)->baseSalaries($rows, $period);
    $lopOf = fn ($slip) => \Modules\AdminModule\Services\PeoplePayroll::lopAmount($slip->breakdown);
    $bonusOf = fn ($slip) => \Modules\AdminModule\Services\PeoplePayroll::bonusTotal($slip->breakdown);
    $bonusLines = fn ($slip) => \Modules\AdminModule\Services\PeoplePayroll::bonusLines($slip->breakdown);
    $salaryPayable = (float) $paying->sum(fn ($slip) => $baseSalaries[$slip->id] ?? 0);
    $lopPayable = (float) $paying->sum(fn ($slip) => $lopOf($slip));
    $bonusPayable = (float) $paying->sum(fn ($slip) => $bonusOf($slip));
    $netPayable = (float) $paying->sum('net');
    $money = function ($amount) {
        $amount = (float) $amount;
        $formatted = '₹'.number_format(abs($amount), 0);
        return $amount < 0 ? '−'.$formatted : $formatted;
    };
    $days = fn ($amount) => rtrim(rtrim(number_format((float) $amount, 1), '0'), '.');
    $dayLabel = fn ($amount) => $days($amount).' '.((float) $amount == 1.0 ? 'day' : 'days');
    $bonusOpen = ($errors->has('label') || $errors->has('amount')) ? (string) request('bonus') : '';
    $attendanceLocked = (bool) ($run && $run->attendance_locked);
@endphp
<div class="people-pay-page">
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
            @if($attendanceLocked)
                <form class="people-pay-calc" method="post" action="{{ route('admin.hr.payroll.build') }}">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <label class="people-pay-check"><input type="checkbox" name="count_missing_days" value="1"> Count each missed day as loss of pay</label>
                    <button class="btn-pw primary" type="submit">Calculate pay</button>
                </form>
            @else
                <p class="people-pay-lock">Lock the attendance first. <a href="{{ route('admin.hr.attendance', ['period' => $period]) }}">Open attendance</a></p>
            @endif
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
        <div><span>Base salary</span><strong>{{ $money($salaryPayable) }}</strong></div>
        <div><span>Loss of pay</span><strong>{{ $money($lopPayable) }}</strong><em>{{ $dayLabel($paying->sum('lop_days')) }}</em></div>
        <div><span>Bonuses</span><strong>{{ $money($bonusPayable) }}</strong></div>
        <div class="is-net"><span>Net payable</span><strong>{{ $money($netPayable) }}</strong><em>{{ $paying->count() }} {{ $paying->count() === 1 ? 'person' : 'people' }}</em></div>
    </div>
</div>
<article class="people-ws-card people-pay-sheet">
    <table class="people-pay-table">
        <thead>
            <tr>
                <th>Person</th>
                <th class="is-money">Base salary</th>
                <th class="is-money">Loss of pay</th>
                <th class="is-money">Bonuses</th>
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
                $canEdit = ! $noSalary && $payslip->status === 'draft' && ! $locked;
                $lines = $bonusLines($payslip);
            @endphp
            <tr class="{{ $noSalary ? 'is-none' : ($included ? '' : 'is-out') }}">
                @php $personName = $payslip->user ? $workspace->displayName($payslip->user) : '—'; @endphp
                <td><span class="people-pay-person">{{ $personName }}@if($noSalary)<span class="people-pay-note">No salary saved</span>@endif</span></td>
                <td class="is-money">{{ $noSalary ? '—' : $money($baseSalaries[$payslip->id] ?? 0) }}</td>
                <td class="is-money">{{ $noSalary ? '—' : $dayLabel($payslip->lop_days).' · '.$money($lopOf($payslip)) }}</td>
                <td class="is-money">
                    @if($noSalary)
                        —
                    @else
                        <span class="people-pay-inline">
                            <span>{{ $money($bonusOf($payslip)) }}</span>
                            @if($canEdit)
                                <button class="btn-pw" type="button" data-pay-bonus
                                    data-id="{{ $payslip->id }}"
                                    data-name="{{ $personName }}"
                                    data-action="{{ route('admin.hr.payroll.bonus', $payslip) }}"
                                    data-lines="{{ json_encode(collect($lines)->map(fn ($line) => ['label' => $line['label'], 'money' => $money($line['amount']), 'remove' => ! empty($line['id']) ? route('admin.hr.payroll.bonus.remove', $line['id']) : null])->values()) }}"
                                    @if($bonusOpen === (string) $payslip->id) data-open="1" @endif>Add</button>
                            @endif
                        </span>
                    @endif
                </td>
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
                    <td class="is-money">{{ $money($salaryPayable) }}</td>
                    <td class="is-money">{{ $dayLabel($paying->sum('lop_days')) }} · {{ $money($lopPayable) }}</td>
                    <td class="is-money">{{ $money($bonusPayable) }}</td>
                    <td class="is-money is-net">{{ $money($netPayable) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</article>
</div>
<div class="modal fade" id="payBonusModal" tabindex="-1" aria-labelledby="payBonusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content people-ws-dept-modal">
            <form id="pay-bonus-form" method="post" action="">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title" id="payBonusModalLabel">Add bonus</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="people-ws-note" id="pay-bonus-person"></p>
                    <div id="pay-bonus-lines"></div>
                    <p class="people-ws-note" id="pay-bonus-error" @if(! $bonusOpen) hidden @endif>{{ $errors->first('label') ?: $errors->first('amount') }}</p>
                    <div class="field">
                        <label for="pay-bonus-label">Name</label>
                        <input id="pay-bonus-label" name="label" type="text" maxlength="120" value="{{ old('label', 'Bonus') }}" required>
                    </div>
                    <div class="field">
                        <label for="pay-bonus-amount">Amount</label>
                        <input id="pay-bonus-amount" name="amount" type="number" step="0.01" value="{{ old('amount') }}" required placeholder="Amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-pw" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-pw primary" type="submit">Add to payslip</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('payBonusModal');
    var form = document.getElementById('pay-bonus-form');
    var linesBox = document.getElementById('pay-bonus-lines');
    var person = document.getElementById('pay-bonus-person');
    var error = document.getElementById('pay-bonus-error');
    var label = document.getElementById('pay-bonus-label');
    var amount = document.getElementById('pay-bonus-amount');
    if (!modal || !form) return;
    function token() {
        var field = form.querySelector('input[name="_token"]');
        return field ? field.value : '';
    }
    function openBonus(button) {
        form.action = button.getAttribute('data-action') || '';
        person.textContent = button.getAttribute('data-name') || '';
        var reopen = button.getAttribute('data-open') === '1';
        if (!reopen) {
            label.value = 'Bonus';
            amount.value = '';
            if (error) error.hidden = true;
        }
        linesBox.innerHTML = '';
        var lines = [];
        try { lines = JSON.parse(button.getAttribute('data-lines') || '[]'); } catch (e) { lines = []; }
        lines.forEach(function (line) {
            var row = document.createElement('div');
            row.className = 'people-pay-modal-line';
            var text = document.createElement('span');
            text.textContent = (line.label || 'Bonus') + ' ' + (line.money || '');
            row.appendChild(text);
            if (line.remove) {
                var remove = document.createElement('form');
                remove.method = 'post';
                remove.action = line.remove;
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = token();
                remove.appendChild(csrf);
                var submit = document.createElement('button');
                submit.type = 'submit';
                submit.className = 'btn-pw';
                submit.textContent = 'Remove';
                remove.appendChild(submit);
                row.appendChild(remove);
            }
            linesBox.appendChild(row);
        });
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        if (window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-pay-bonus]');
        if (!button) return;
        openBonus(button);
    });
    var pending = document.querySelector('[data-pay-bonus][data-open="1"]');
    if (pending) openBonus(pending);
})();
</script>
