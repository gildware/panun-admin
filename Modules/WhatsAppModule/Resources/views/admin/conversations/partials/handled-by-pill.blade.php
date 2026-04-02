@php
    $key = $handledByKey ?? 'AI';
    $label = $handledByLabel ?? 'AI';
    $isAi = ($key === 'AI' || $label === 'AI');
    $onUnread = !empty($onUnread);
@endphp
<span class="wa-hand-pill {{ $isAi ? 'wa-hand-pill--ai' : 'wa-hand-pill--agent' }} {{ $onUnread ? 'wa-hand-pill--on-unread' : '' }}">
    @if($isAi)
        {{ translate('AI') }}
    @else
        {{ $label }}
    @endif
</span>
