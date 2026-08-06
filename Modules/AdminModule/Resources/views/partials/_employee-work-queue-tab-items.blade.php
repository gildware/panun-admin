@php
    $items = $items ?? [];
    $columns = $columns ?? [];
    $listDisplay = $listDisplay ?? 'table';
    $emptyIcon = $emptyIcon ?? match ($listDisplay) {
        'whatsapp_cards' => 'forum',
        'cards' => 'task_alt',
        default => 'inbox',
    };
    $emptyLabel = $emptyLabel ?? translate('no_data_available');
@endphp

@if(! empty($items))
    @if($listDisplay === 'cards')
        <div class="work-queue-card-list">
            @foreach($items as $item)
                <a href="{{ $item['url'] ?? '#' }}"
                   class="work-queue-item-card {{ ! empty($item['is_overdue']) ? 'is-overdue' : '' }}">
                    <div class="work-queue-item-card-top">
                        <span class="work-queue-item-card-title">{{ $item['name'] ?? '—' }}</span>
                        <div class="work-queue-item-card-top-right">
                            <span class="urgency-pill urgency-{{ $item['urgency'] ?? 'medium' }}">{{ $item['urgency_label'] ?? '—' }}</span>
                            @if(! empty($item['assignee_label']))
                                <span class="assignee-pill">{{ $item['assignee_label'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="work-queue-item-card-meta">
                        <span class="type-pill">{{ $item['type'] ?? '—' }}</span>
                        <span class="work-queue-item-card-date">{{ $item['datetime_display'] ?? '—' }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @elseif($listDisplay === 'whatsapp_cards')
        <div class="work-queue-whatsapp-card-list">
            @foreach($items as $item)
                <a href="{{ $item['url'] ?? '#' }}" class="work-queue-whatsapp-card {{ ($item['unread_count'] ?? 0) > 0 ? 'has-unread' : '' }}">
                    <div class="work-queue-whatsapp-card-head">
                        <div class="work-queue-whatsapp-card-user">
                            <span class="work-queue-whatsapp-avatar material-symbols-outlined">person</span>
                            <div class="work-queue-whatsapp-user-text">
                                <span class="work-queue-whatsapp-name">{{ $item['name'] ?? '—' }}</span>
                                @if(! empty($item['phone']) && ($item['phone'] ?? '') !== ($item['name'] ?? ''))
                                    <span class="work-queue-whatsapp-phone">{{ $item['phone'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="work-queue-whatsapp-card-meta">
                            <span class="work-queue-whatsapp-time">{{ $item['datetime_display'] ?? '—' }}</span>
                            @if(($item['unread_count'] ?? 0) > 0)
                                <span class="work-queue-whatsapp-unread">{{ $item['unread_count'] }}</span>
                            @endif
                            @if(! empty($item['handler_label']))
                                <span class="work-queue-whatsapp-handler">{{ $item['handler_label'] }}</span>
                            @endif
                        </div>
                    </div>
                    <p class="work-queue-whatsapp-message">{{ $item['message_preview'] ?? '—' }}</p>
                    @if(! empty($item['tags']) || ! empty($item['status_label']))
                        <div class="work-queue-whatsapp-tags">
                            @if(! empty($item['status_label']))
                                <span class="work-queue-whatsapp-status">{{ $item['status_label'] }}</span>
                            @endif
                            @foreach($item['tags'] ?? [] as $tag)
                                <span class="work-queue-whatsapp-tag" style="--tag-color: {{ $tag['color'] ?? '#64748b' }}">{{ $tag['name'] ?? '' }}</span>
                            @endforeach
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div class="work-queue-table-wrap">
            <table class="work-queue-table">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th>{{ $column['label'] ?? '' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="{{ ! empty($item['is_overdue']) ? 'is-overdue' : '' }}">
                            @foreach($columns as $column)
                                @switch($column['key'] ?? '')
                                    @case('name')
                                        <td class="col-name">
                                            <a href="{{ $item['url'] ?? '#' }}" class="work-queue-row-link">
                                                <span class="cell-primary">{{ $item['name'] ?? '—' }}</span>
                                                @if(! empty($item['name_sub']))
                                                    <span class="cell-secondary">{{ $item['name_sub'] }}</span>
                                                @endif
                                            </a>
                                        </td>
                                        @break
                                    @case('assignee')
                                        <td class="col-assignee">
                                            <span class="assignee-pill">{{ $item['assignee_label'] ?? translate('Unassigned') }}</span>
                                        </td>
                                        @break
                                    @case('type')
                                        <td class="col-type">
                                            <span class="type-pill">{{ $item['type'] ?? '—' }}</span>
                                        </td>
                                        @break
                                    @case('datetime')
                                        <td class="col-datetime">
                                            <span class="datetime-main">{{ $item['datetime_display'] ?? '—' }}</span>
                                        </td>
                                        @break
                                    @case('urgency')
                                        <td class="col-urgency">
                                            <span class="urgency-pill urgency-{{ $item['urgency'] ?? 'medium' }}">{{ $item['urgency_label'] ?? '—' }}</span>
                                        </td>
                                        @break
                                @endswitch
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@else
    <div class="work-queue-empty">
        <span class="material-symbols-outlined">{{ $emptyIcon }}</span>
        <span>{{ $emptyLabel }}</span>
    </div>
@endif
