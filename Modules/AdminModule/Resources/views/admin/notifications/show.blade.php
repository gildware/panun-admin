@extends('adminmodule::layouts.master')

@section('title', translate('Notification_Details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="page-title-wrap mb-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <h2 class="page-title mb-0">{{ translate('Notification_Details') }}</h2>
                        <a href="{{ route('admin.notifications.index') }}"
                           class="btn btn--secondary btn-sm"
                           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                            <span class="material-icons">arrow_back</span>
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-3 align-items-start mb-4">
                                <div class="avatar title-color flex-shrink-0">
                                    <span class="material-symbols-outlined fs-2">{{ $notification->iconName() }}</span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                        <h3 class="mb-0">{{ $notification->title }}</h3>
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

                            <div class="d-flex flex-wrap gap-2">
                                @if($notification->action_url)
                                    <a href="{{ $notification->action_url }}"
                                       class="btn btn--primary"
                                       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                        {{ translate('View_Details') }}
                                    </a>
                                @endif
                                <a href="{{ route('admin.notifications.index') }}"
                                   class="btn btn--secondary"
                                   @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
                                    {{ translate('view_all') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
