@php
    $displayValue = $displayValue ?? null;
    $count = (int) ($count ?? 0);
    $hasTotal = array_key_exists('total', get_defined_vars()) && $total !== null && $total !== '';
    $total = $hasTotal ? (int) $total : null;
    $isPercent = ! empty($isPercent);
    $ofClass = $ofClass ?? 'mc-of';

    if ($displayValue !== null && $displayValue !== '') {
        $primary = (string) $displayValue;
        $showTotal = false;
    } elseif ($isPercent) {
        $primary = rtrim(rtrim(number_format((float) $count, 1), '0'), '.').'%';
        $showTotal = false;
    } else {
        $primary = number_format($count);
        $showTotal = $hasTotal && ($total > 0 || $count > 0);
    }
@endphp
{{ $primary }}@if($showTotal) <span class="{{ $ofClass }}">/ {{ number_format($total) }}</span>@endif
