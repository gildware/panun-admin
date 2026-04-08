{{-- Expects: $name, $defaultDescription, $overrideDescription --}}
@php
    $payload = [
        'name' => $name,
        'default' => $defaultDescription ?? '',
        'override' => $overrideDescription ?? '',
    ];
@endphp
<button type="button"
    class="btn btn-link btn-sm p-0 ms-1 align-middle wa-ai-tool-info-btn text-secondary"
    data-bs-toggle="modal"
    data-bs-target="#waAiToolInfoModal"
    data-wa-ai-payload='@json($payload)'
    title="{{ __('whatsapp_ai.tool_info_tooltip') }}"
    aria-label="{{ __('whatsapp_ai.tool_info_aria', ['tool' => $name]) }}">
    <span class="wa-ai-tool-info-i" aria-hidden="true">i</span>
</button>
