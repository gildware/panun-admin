@php
    $required = !empty($required);
    $hint = $hint ?? null;
    $for = $for ?? null;
@endphp
<label @if($for) for="{{ $for }}" @endif class="voice-field-label form-label d-flex align-items-center gap-1 mb-2">
    <span>{{ $label }}@if($required)<span class="text-danger ms-1" aria-hidden="true">*</span>@endif</span>
    @if($hint)
        <i class="material-symbols-outlined voice-field-info"
           data-bs-toggle="tooltip"
           data-bs-placement="top"
           data-bs-html="false"
           title="{{ $hint }}"
           tabindex="0"
           role="img"
           aria-label="{{ $hint }}">info</i>
    @endif
</label>
