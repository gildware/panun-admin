@php
    $__remarks = trim((string) ($remarks ?? ''));
@endphp
@if ($__remarks !== '')
    <p class="fz-12 mb-0 mt-1 text-break">
        <span class="fw-semibold">{{ translate('Visit_remarks') }}:</span>
        {{ $__remarks }}
    </p>
@endif
