@extends('adminmodule::layouts.master')

@section('title', translate('Notifications'))

@section('content')
    @php
        use Modules\AdminModule\Entities\UserNotification;
    @endphp
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Notifications') }}</h2>
                            <p class="text-muted mb-0 small">
                                <span class="text-danger fw-semibold">{{ $externalUnreadCount + $internalUnreadCount }}</span> {{ translate('unread') }}
                                &middot;
                                <span>{{ $externalReadCount + $internalReadCount }}</span> {{ translate('read') }}
                            </p>
                        </div>
                        @if(($externalUnreadCount + $internalUnreadCount) > 0)
                            <form action="{{ route('admin.notifications.mark_all_read_page') }}" method="POST">
                                @csrf
                                @if($filter)
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                @if($category)
                                    <input type="hidden" name="category" value="{{ $category }}">
                                @endif
                                <button type="submit" class="btn btn--primary btn-sm">
                                    {{ translate('Mark_all_read') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <ul class="nav nav-pills gap-2 flex-wrap">
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', array_filter(['category' => $category])) }}"
                                       class="nav-link {{ empty($filter) ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('all') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', array_filter(['filter' => 'unread', 'category' => $category])) }}"
                                       class="nav-link {{ $filter === 'unread' ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('unread') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', array_filter(['filter' => 'read', 'category' => $category])) }}"
                                       class="nav-link {{ $filter === 'read' ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('read') }}
                                    </a>
                                </li>
                                <li class="nav-item ms-md-auto">
                                    <a href="{{ route('admin.notifications.index', array_filter(['filter' => $filter, 'category' => UserNotification::CATEGORY_EXTERNAL])) }}"
                                       class="nav-link {{ ($category ?? null) === UserNotification::CATEGORY_EXTERNAL ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('External') }}
                                        @if($externalUnreadCount > 0)
                                            <span class="badge bg-danger rounded-pill ms-1">{{ $externalUnreadCount }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', array_filter(['filter' => $filter, 'category' => UserNotification::CATEGORY_INTERNAL])) }}"
                                       class="nav-link {{ ($category ?? null) === UserNotification::CATEGORY_INTERNAL ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('Internal') }}
                                        @if($internalUnreadCount > 0)
                                            <span class="badge bg-danger rounded-pill ms-1">{{ $internalUnreadCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            @if($notifications->isEmpty())
                                <div class="text-center text-muted py-5">
                                    {{ translate('No_notification_found') }}
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @include('adminmodule::admin.partials._notification-items', [
                                        'notifications' => $notifications,
                                        'compact' => false,
                                    ])
                                </div>

                                @if($notifications->hasPages())
                                    <div class="p-3 border-top">
                                        {{ $notifications->links() }}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
