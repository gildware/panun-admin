@php
    $created = $chat->created_at ?? null;
    $phone = $chat->phone ?? '';
    $name = trim($chat->name ?? '');
    $phoneDisplay = $displayPhone($phone);
    $displayLine = $chat->display_line ?? ($name !== '' ? $name . ' (' . $phoneDisplay . ')' : $phoneDisplay);
    $direction = strtoupper($chat->direction ?? '');
    $status = strtolower($chat->status ?? '');
    $statusIcon = '';
    $hasUnread = !empty($chat->unread_count);
    if ($direction === 'OUT') {
        if ($status === 'sent') {
            $statusIcon = '✓';
        } elseif ($status === 'delivered') {
            $statusIcon = '✓✓';
        } elseif ($status === 'read') {
            $statusIcon = '✓✓';
        }
    }
    $handledByLabel = $chat->handled_by_label ?? 'AI';
    $handledByKey = $chat->handled_by_key ?? 'AI';
    $lastMessageAt = \Modules\WhatsAppModule\Support\WhatsAppMessageTime::formatListLabel($created);
    $chatSt = isset($chat->chat_status) && is_array($chat->chat_status) ? $chat->chat_status : null;
    $chatTagList = isset($chat->chat_tags) && is_array($chat->chat_tags) ? $chat->chat_tags : [];
@endphp
<div class="whatsapp-chat-item border-bottom p-3 cursor-pointer{{ $hasUnread ? ' bg-primary text-white' : '' }}"
     data-phone="{{ e($phone) }}"
     data-wa-display-line="{{ e($displayLine) }}"
     title="{{ e($phone) }}"
     role="button">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <strong class="text-truncate min-w-0{{ $hasUnread ? ' text-white' : '' }}" title="{{ e($displayLine) }}">{{ $displayLine }}</strong>
        <div class="flex-shrink-0">
            @include('whatsappmodule::admin.conversations.partials.system-link-pills', [
                'systemLink' => $chat->system_link ?? [],
                'onUnread' => $hasUnread,
                'showNames' => false,
            ])
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-start gap-2 mt-2">
        <div class="wa-chat-preview fz-12 flex-grow-1 min-w-0{{ $hasUnread ? ' text-white' : ' text-muted' }}">
            {{ $chat->message_text ?? '' }}
        </div>
        <div class="flex-shrink-0 d-flex align-items-center gap-1 pt-0">
            @if(!empty($chat->unread_count))
                <span class="badge wa-unread-count-badge {{ $hasUnread ? 'bg-light text-primary' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                    {{ (int) $chat->unread_count }}
                </span>
            @endif
            @if($statusIcon)
                <span class="fz-12 {{ $hasUnread ? 'text-white' : ($status === 'read' ? 'text-primary' : 'text-muted') }}">{{ $statusIcon }}</span>
            @endif
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
        <div class="min-w-0">
            @include('whatsappmodule::admin.conversations.partials.handled-by-pill', [
                'handledByKey' => $handledByKey,
                'handledByLabel' => $handledByLabel,
                'onUnread' => $hasUnread,
            ])
        </div>
        <span class="wa-chat-item-row3-time {{ $hasUnread ? 'text-white-50' : 'text-muted' }}">{{ $lastMessageAt }}</span>
    </div>
    @if(!empty($chat->human_support_requested_at) && empty($humanSupportTab ?? false))
        <div class="fz-11 mt-1">
            <span class="badge bg-warning text-dark">{{ translate('Wants human') }}</span>
        </div>
    @endif
    @php
        $adAttr = isset($chat->ad_attribution) && is_array($chat->ad_attribution) ? $chat->ad_attribution : null;
        $fromFbAd = !empty($adAttr['from_ad']);
        $adLabel = $adAttr['platform_label'] ?? translate('WhatsApp Ad');
        $adName = $adAttr['display_name'] ?? ($adAttr['headline'] ?? '');
        if (\Modules\LeadManagement\Entities\AdSource::isBadAdName($adName)) {
            $adName = '';
        }
    @endphp
    @if($fromFbAd)
        <div class="fz-11 mt-1 d-flex align-items-center gap-1 min-w-0">
            @if(!empty($adAttr['image_url']))
                <img src="{{ $adAttr['image_url'] }}" alt="" class="rounded flex-shrink-0" style="width:18px;height:18px;object-fit:cover;" loading="lazy" onerror="this.style.display='none'">
            @endif
            <span class="badge bg-info text-dark">{{ $adLabel }}</span>
            @if($adName !== '')
                <span class="text-muted text-truncate">{{ \Illuminate\Support\Str::limit($adName, 40) }}</span>
            @endif
        </div>
    @endif
    @if($chatSt || !empty($chatTagList))
        <div class="wa-chat-item-meta mt-2 d-flex flex-wrap align-items-center gap-1">
            @if($chatSt)
                @php $bucket = $chatSt['bucket'] ?? 'open'; @endphp
                <span class="badge fz-11 {{ $bucket === 'closed' ? 'bg-secondary' : 'bg-success' }}{{ $hasUnread ? ' text-white' : '' }}">{{ e($chatSt['name'] ?? '') }}</span>
            @endif
            @foreach($chatTagList as $tg)
                @php $tc = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($tg['color'] ?? '')) ? $tg['color'] : '#6c757d'; @endphp
                <span class="badge fz-11 wa-chat-tag-pill" style="background:{{ e($tc) }};color:#fff;">{{ e($tg['name'] ?? '') }}</span>
            @endforeach
        </div>
    @endif
    @include('whatsappmodule::admin.conversations.partials._active-chat-item-lead-counts', [
        'leadTypeCounts' => $chat->lead_type_counts ?? [],
        'onUnread' => $hasUnread,
    ])
</div>
