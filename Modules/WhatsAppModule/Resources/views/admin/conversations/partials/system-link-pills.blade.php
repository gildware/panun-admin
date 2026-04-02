@php
    $slRaw = $systemLink ?? [];
    $sl = is_array($slRaw) ? $slRaw : json_decode(json_encode($slRaw), true);
    if (!is_array($sl)) {
        $sl = [];
    }
    $customer = $sl['customer'] ?? [];
    $provider = $sl['provider'] ?? [];
    if (!is_array($customer)) {
        $customer = json_decode(json_encode($customer), true) ?: [];
    }
    if (!is_array($provider)) {
        $provider = json_decode(json_encode($provider), true) ?: [];
    }
    $onUnread = !empty($onUnread);
    $showNames = $showNames ?? false;
@endphp
<div class="wa-sys-pills {{ $onUnread ? 'wa-sys-pills--on-unread' : '' }} d-flex align-items-center gap-1 flex-wrap min-w-0">
    @if(!empty($customer['url']))
        <a href="{{ e($customer['url']) }}" target="_blank" rel="noopener"
           class="wa-sys-pill wa-sys-pill--customer text-decoration-none"
           onclick="event.stopPropagation();">{{ translate('whatsapp_system_customer') }}</a>
        @if($showNames && !empty($customer['name']))
            <a href="{{ e($customer['url']) }}" target="_blank" rel="noopener"
               class="wa-sys-name-link small text-decoration-none text-truncate d-inline-block"
               style="max-width: 9rem;"
               title="{{ e($customer['name']) }}"
               onclick="event.stopPropagation();">{{ e($customer['name']) }}</a>
        @endif
    @endif
    @if(!empty($customer['url']) && !empty($provider['url']))
        <span class="wa-sys-pill-sep {{ $onUnread ? 'text-white-50' : 'text-muted' }} px-0">|</span>
    @endif
    @if(!empty($provider['url']))
        <a href="{{ e($provider['url']) }}" target="_blank" rel="noopener"
           class="wa-sys-pill wa-sys-pill--provider text-decoration-none"
           onclick="event.stopPropagation();">{{ translate('whatsapp_system_provider') }}</a>
        @if($showNames && !empty($provider['name']))
            <a href="{{ e($provider['url']) }}" target="_blank" rel="noopener"
               class="wa-sys-name-link small text-decoration-none text-truncate d-inline-block"
               style="max-width: 9rem;"
               title="{{ e($provider['name']) }}"
               onclick="event.stopPropagation();">{{ e($provider['name']) }}</a>
        @endif
    @endif
    @if(empty($customer['url']) && empty($provider['url']))
        <span class="wa-sys-pill wa-sys-pill--none">{{ translate('whatsapp_not_in_system') }}</span>
    @endif
</div>
