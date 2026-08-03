@php
    use Modules\AdminModule\Entities\UserNotification;

    $category = $category ?? UserNotification::CATEGORY_EXTERNAL;
    $isInternal = $category === UserNotification::CATEGORY_INTERNAL;
    $countId = $isInternal ? 'notification_internal_count' : 'notification_external_count';
    $listId = $isInternal ? 'show-notification-list-internal' : 'show-notification-list-external';
    $unreadCount = $isInternal
        ? (int) ($notificationInternalUnreadCount ?? 0)
        : (int) ($notificationExternalUnreadCount ?? 0);
    $icon = $isInternal ? 'assignment_ind' : 'public';
    $title = $isInternal ? translate('Internal_Notifications') : translate('External_Notifications');
    $viewAllCategory = $category;
    $wrapperClass = $wrapperClass ?? 'notification update-notification pe--12';
    $toggleClass = $toggleClass ?? 'header-icon count-btn notification-icon';
    $isTopChrome = $isTopChrome ?? false;
@endphp

<div class="{{ $isTopChrome ? 'dropdown top-utility-item' : $wrapperClass }}">
    @if($isTopChrome)
        <button type="button"
                class="top-utility-icon-btn {{ $toggleClass }}"
                data-bs-toggle="dropdown"
                data-bs-offset="0,6"
                data-bs-popper-config='{"strategy":"fixed"}'
                title="{{ $title }}"
                aria-label="{{ $title }}">
            <span class="material-symbols-outlined">{{ $icon }}</span>
            <span class="count" id="{{ $countId }}" style="display:{{ $unreadCount > 0 ? 'flex' : 'none' }};">{{ $unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : '' }}</span>
        </button>
    @else
        <a href="#"
           class="{{ $toggleClass }}"
           data-bs-toggle="dropdown"
           title="{{ $title }}"
           aria-label="{{ $title }}">
            <span class="material-symbols-outlined">{{ $icon }}</span>
            <span class="count" id="{{ $countId }}" style="display:{{ $unreadCount > 0 ? 'flex' : 'none' }};">{{ $unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : '' }}</span>
        </a>
    @endif
    <div class="dropdown-menu {{ $isTopChrome ? 'dropdown-menu-end' : 'dropdown-menu-right' }} p-0" style="min-width:22rem;max-width:26rem;">
        <div class="show-notification-list" id="{{ $listId }}" data-notification-category="{{ $category }}" style="max-height:24rem;overflow-y:auto;"></div>
        <div class="border-top py-2 px-3 text-center bg-white">
            <a href="{{ route('admin.notifications.index', ['category' => $viewAllCategory]) }}"
               class="btn btn-sm btn-link text-decoration-none fw-semibold js-view-all-notifications"
               @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                {{ translate('view_all') }}
            </a>
        </div>
    </div>
</div>
