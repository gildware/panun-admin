@extends('adminmodule::layouts.master')

@section('title', translate('Notifications'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div>
                            <h2 class="page-title mb-1">{{ translate('Notifications') }}</h2>
                            <p class="text-muted mb-0 small">
                                <span class="text-danger fw-semibold">{{ $unreadCount }}</span> {{ translate('unread') }}
                                &middot;
                                <span>{{ $readCount }}</span> {{ translate('read') }}
                            </p>
                        </div>
                        @if($unreadCount > 0)
                            <form action="{{ route('admin.notifications.mark_all_read_page') }}" method="POST">
                                @csrf
                                @if($filter)
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                @endif
                                <button type="submit" class="btn btn--primary btn-sm">
                                    {{ translate('Mark_all_read') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <ul class="nav nav-pills gap-2">
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index') }}"
                                       class="nav-link {{ empty($filter) ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('all') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}"
                                       class="nav-link {{ $filter === 'unread' ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('unread') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index', ['filter' => 'read']) }}"
                                       class="nav-link {{ $filter === 'read' ? 'active' : '' }}"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('read') }}
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
                                    @foreach($notifications as $notification)
                                        <a href="{{ route('admin.notifications.show', $notification->id) }}"
                                           class="list-group-item list-group-item-action px-3 py-3 {{ $notification->isUnread() ? 'bg-light' : '' }}"
                                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                            <div class="d-flex gap-3 align-items-start">
                                                <div class="avatar title-color flex-shrink-0">
                                                    <span class="material-symbols-outlined">{{ $notification->iconName() }}</span>
                                                </div>
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
                                        </a>
                                    @endforeach
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
