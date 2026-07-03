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
        <button type="button"
                class="dropdown-item-text media gap-3 js-admin-notification-item border-0 bg-transparent w-100 text-start {{ $notification->isUnread() ? 'bg-light' : '' }}"
                data-notification-id="{{ $notification->id }}"
                data-notification-type="{{ $notification->type }}"
                data-action-url="{{ $notification->action_url }}">
            @include('adminmodule::admin.partials._notification-avatar', ['notification' => $notification])
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
                @include('adminmodule::admin.partials._notification-time', ['notification' => $notification])
            </div>
        </button>
        <div class="dropdown-divider mb-0"></div>
    @endforeach
@endif
