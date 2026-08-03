@php
    $compact = $compact ?? false;
@endphp

@if($notifications->isEmpty())
    <div class="dropdown-item-text text-center text-muted py-4">
        {{ translate('No_notification_found') }}
    </div>
@else
    @foreach($notifications as $notification)
        <button type="button"
                class="{{ $compact ? 'dropdown-item-text media gap-3 js-admin-notification-item border-0 bg-transparent w-100 text-start' : 'list-group-item list-group-item-action px-3 py-3 border-0 js-admin-notification-list-item' }} {{ $notification->isUnread() ? 'bg-light' : '' }}"
                data-notification-id="{{ $notification->id }}"
                data-notification-type="{{ $notification->type }}"
                data-action-url="{{ $notification->action_url }}">
            @if($compact)
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
            @else
                <div class="d-flex gap-3 align-items-start">
                    @include('adminmodule::admin.partials._notification-avatar', ['notification' => $notification])
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                            <h5 class="mb-0 {{ $notification->isUnread() ? 'fw-bold' : '' }}">
                                {{ $notification->title }}
                            </h5>
                            @if($notification->isUnread())
                                <span class="badge bg-danger rounded-pill">{{ translate('new') }}</span>
                            @endif
                            <span class="badge bg-secondary-subtle text-secondary">{{ $notification->typeLabel() }}</span>
                        </div>
                        @if($notification->body)
                            <p class="mb-1 text-muted fz-14">{{ Str::limit($notification->body, 160) }}</p>
                        @endif
                        @include('adminmodule::admin.partials._notification-time', ['notification' => $notification])
                    </div>
                    <span class="material-symbols-outlined text-muted flex-shrink-0">chevron_right</span>
                </div>
            @endif
        </button>
        @if($compact)
            <div class="dropdown-divider mb-0"></div>
        @endif
    @endforeach
@endif
