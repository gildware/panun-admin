<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1a1c38; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .net { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Panun Kaergar</h1>
    <p class="muted">Payslip · {{ \Carbon\Carbon::createFromFormat('Y-m', $payslip->period)->format('F Y') }}</p>
    <p><strong>{{ $name }}</strong><br>{{ $profile->employee_code ?? '' }} · {{ $profile->job_title ?? '' }}</p>
    @php $lines = $payslip->breakdown ?? []; @endphp
    <table>
        @foreach(($lines['earnings'] ?? []) as $line)
            @if((float) $line['amount'] != 0)
                <tr><td>{{ $line['label'] }}</td><td style="text-align:right">₹{{ number_format((float) $line['amount'], 2) }}</td></tr>
            @endif
        @endforeach
        @if((float) ($lines['lop_amount'] ?? 0) > 0)
            <tr><td>Loss of pay ({{ rtrim(rtrim(number_format((float) $payslip->lop_days, 1), '0'), '.') }} days)</td><td style="text-align:right">−₹{{ number_format((float) $lines['lop_amount'], 2) }}</td></tr>
        @endif
        <tr><td>Gross</td><td style="text-align:right">₹{{ number_format((float) $payslip->gross, 2) }}</td></tr>
        @foreach(($lines['deductions'] ?? []) as $line)
            @if((float) $line['amount'] != 0)
                <tr><td>{{ $line['label'] }}</td><td style="text-align:right">₹{{ number_format((float) $line['amount'], 2) }}</td></tr>
            @endif
        @endforeach
        @if(($lines['deductions'] ?? []) === [])
            <tr><td>Deductions</td><td style="text-align:right">₹{{ number_format((float) $payslip->deductions, 2) }}</td></tr>
        @endif
        @if((float) ($lines['adjustment'] ?? 0) != 0)
            <tr><td>Adjustment</td><td style="text-align:right">₹{{ number_format((float) $lines['adjustment'], 2) }}</td></tr>
        @endif
        <tr><td class="net">Net pay</td><td class="net" style="text-align:right">₹{{ number_format((float) $payslip->net, 2) }}</td></tr>
        @if((float) ($lines['employer_pf'] ?? 0) > 0)
            <tr><td>Employer provident fund (not deducted)</td><td style="text-align:right">₹{{ number_format((float) $lines['employer_pf'], 2) }}</td></tr>
        @endif
    </table>
    <p class="muted">Status: {{ ucfirst($payslip->status) }}@if($payslip->published_at) · published {{ $payslip->published_at->format('j M Y') }}@endif</p>
</body>
</html>
