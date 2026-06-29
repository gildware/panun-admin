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
                            @include('adminmodule::admin.partials._notification-detail-content', ['notification' => $notification])
                            <div class="d-flex flex-wrap gap-2 mt-3">
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
