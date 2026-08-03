@php
    use Modules\AdminModule\Entities\UserNotification;

    $category = $category ?? UserNotification::CATEGORY_EXTERNAL;
    $isInternal = $category === UserNotification::CATEGORY_INTERNAL;
    $notifications = $notifications ?? collect();
    $unreadCount = (int) ($unreadCount ?? 0);
    $readCount = (int) ($readCount ?? 0);
    $compact = $compact ?? true;
    $dropdownTitle = $isInternal ? translate('Internal_Notifications') : translate('External_Notifications');
@endphp

<div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between gap-2">
    <div>
        <span class="fw-semibold">{{ $dropdownTitle }}</span>
        <div class="small text-muted mt-1">
            <span class="text-danger fw-semibold">{{ $unreadCount }}</span> {{ translate('unread') }}
            &middot;
            <span>{{ $readCount }}</span> {{ translate('read') }}
        </div>
    </div>
    @if($unreadCount > 0)
        <button type="button"
                class="btn btn-sm btn-outline-primary js-mark-all-notifications-read"
                data-notification-category="{{ $category }}"
                title="{{ translate('Mark_all_as_read') }}">
            {{ translate('Mark_all_read') }}
        </button>
    @endif
</div>

@include('adminmodule::admin.partials._notification-items', [
    'notifications' => $notifications,
    'compact' => $compact,
])
