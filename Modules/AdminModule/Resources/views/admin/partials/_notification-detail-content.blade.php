<div class="d-flex gap-3 align-items-start mb-4">
    @include('adminmodule::admin.partials._notification-avatar', ['notification' => $notification, 'avatarSize' => 48])
    <div class="flex-grow-1">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <h3 class="mb-0 h5">{{ $notification->title }}</h3>
            <span class="badge bg-secondary-subtle text-secondary">{{ $notification->typeLabel() }}</span>
            @if($notification->isUnread())
                <span class="badge bg-danger rounded-pill">{{ translate('new') }}</span>
            @else
                <span class="badge bg-success-subtle text-success">{{ translate('read') }}</span>
            @endif
        </div>
        @include('adminmodule::admin.partials._notification-time', ['notification' => $notification])
        @if($notification->read_at)
            <div class="small text-muted mt-1">
                {{ translate('Read_at') }}: {{ $notification->read_at->format('d M Y, h:i A') }}
            </div>
        @endif
    </div>
</div>

@if($notification->body)
    <div class="mb-4">
        <h5 class="mb-2">{{ translate('Description') }}</h5>
        <p class="mb-0 text-muted">{{ $notification->body }}</p>
    </div>
@endif

@if($notification->action_url)
    <div class="d-flex {{ !empty($inModal) ? 'justify-content-center' : 'flex-wrap gap-2' }}">
        <a href="{{ $notification->action_url }}"
           class="btn btn--primary"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            {{ $notification->actionButtonLabel() }}
        </a>
    </div>
@endif
