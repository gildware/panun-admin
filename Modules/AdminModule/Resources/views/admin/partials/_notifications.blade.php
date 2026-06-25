@php
    $unreadCount = $unreadCount ?? 0;
    $readCount = $readCount ?? 0;
@endphp

<div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between gap-2">
    <div>
        <span class="fw-semibold">{{ translate('Notifications') }}</span>
        <div class="small text-muted mt-1">
            <span class="text-danger fw-semibold">{{ $unreadCount }}</span> {{ translate('unread') }}
            &middot;
            <span>{{ $readCount }}</span> {{ translate('read') }}
        </div>
    </div>
    @if($unreadCount > 0)
        <button type="button"
                class="btn btn-sm btn-outline-primary js-mark-all-notifications-read"
                title="{{ translate('Mark_all_as_read') }}">
            {{ translate('Mark_all_read') }}
        </button>
    @endif
</div>

@if($notifications->isEmpty())
    <div class="dropdown-item-text text-center text-muted py-4">
        {{ translate('No_notification_found') }}
    </div>
@else
    @foreach($notifications as $notification)
        @php
            $to_time = strtotime($notification->created_at);
            $from_time = strtotime(now());
            $diff = round(abs($to_time - $from_time) / 60, 2);
            $time = $diff . ' ' . translate('min');
            if ($diff > 60) {
                $diff = round($diff / 60);
                $time = $diff . ' ' . translate('hr');
                if ($diff > 24) {
                    $diff = round($diff / 24);
                    $time = $diff . ' ' . translate('day');
                }
            }
            $icon = match ($notification->type) {
                'booking' => 'event_available',
                'chat_message' => 'chat',
                'provider_request' => 'person_add',
                'withdraw_request' => 'payments',
                default => 'notifications',
            };
        @endphp
        <a href="{{ $notification->action_url ?? '#' }}"
           class="dropdown-item-text media gap-3 js-admin-notification-item {{ $notification->isUnread() ? 'bg-light' : '' }}"
           data-notification-id="{{ $notification->id }}"
           data-action-url="{{ $notification->action_url ?? '' }}">
            <div class="avatar title-color hover-color-c2 flex-shrink-0">
                <span class="material-symbols-outlined">{{ $icon }}</span>
            </div>
            <div class="media-body">
                <h5 class="card-title mb-1 {{ $notification->isUnread() ? 'fw-bold' : '' }}">
                    {{ $notification->title }}
                    @if($notification->isUnread())
                        <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.55rem;">{{ translate('new') }}</span>
                    @endif
                </h5>
                @if($notification->body)
                    <p class="card-text fz-14 mb-1">{{ Str::limit($notification->body, 120) }}</p>
                @endif
                <span class="card-text fz-12 text-opacity-75">{{ $time }} {{ translate('ago') }}</span>
            </div>
        </a>
        <div class="dropdown-divider mb-0"></div>
    @endforeach
@endif
