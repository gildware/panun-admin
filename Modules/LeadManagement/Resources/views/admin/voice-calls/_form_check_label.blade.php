@php
    $hint = $hint ?? null;
    $for = $for ?? '';
@endphp
<label class="form-check-label d-inline-flex align-items-center gap-1 flex-wrap" for="{{ $for }}">
    <span>{{ $label }}</span>
    @if($hint)
        <i class="material-symbols-outlined voice-field-info"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           title="{{ $hint }}"
           tabindex="0"
           role="img"
           aria-label="{{ $hint }}">info</i>
    @endif
</label>
