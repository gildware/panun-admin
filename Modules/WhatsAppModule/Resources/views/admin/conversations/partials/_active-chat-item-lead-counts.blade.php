@php
    use Modules\LeadManagement\Entities\Lead;

    $leadTypeCounts = is_array($leadTypeCounts ?? null) ? $leadTypeCounts : [];
    $segments = [
        Lead::TYPE_PROVIDER => [
            'label' => translate('whatsapp_chat_item_lead_provider'),
            'badgeClass' => 'bg-primary text-white',
        ],
        Lead::TYPE_CUSTOMER => [
            'label' => translate('whatsapp_chat_item_lead_customer'),
            'badgeClass' => 'bg-success text-white',
        ],
        Lead::TYPE_INVALID => [
            'label' => translate('whatsapp_chat_item_lead_invalid'),
            'badgeClass' => 'bg-danger text-white',
        ],
        Lead::TYPE_UNKNOWN => [
            'label' => translate('whatsapp_chat_item_lead_unknown'),
            'badgeClass' => 'bg-warning text-dark',
        ],
    ];
    $visible = [];
    foreach ($segments as $type => $meta) {
        $count = (int) ($leadTypeCounts[$type] ?? 0);
        if ($count <= 0) {
            continue;
        }
        $visible[] = array_merge($meta, ['count' => $count]);
    }
@endphp
@if(!empty($visible))
    <div class="wa-chat-item-lead-counts mt-2 d-flex flex-wrap align-items-center gap-1">
        @foreach($visible as $index => $item)
            @if($index > 0)
                <span class="wa-chat-item-lead-count-sep fz-11 {{ !empty($onUnread) ? 'text-white-50' : 'text-muted' }}">|</span>
            @endif
            <span class="badge rounded-pill fz-11 {{ $item['badgeClass'] }}">
                {{ $item['label'] }} ({{ $item['count'] }})
            </span>
        @endforeach
    </div>
@endif
