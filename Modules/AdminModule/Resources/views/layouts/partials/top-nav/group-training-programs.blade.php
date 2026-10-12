<div class="top-nav-item">
    <a href="{{ route('admin.training.index') }}"
       class="top-nav-trigger {{ request()->routeIs('admin.training.index', 'admin.training.content', 'admin.training.questions', 'admin.training.ai', 'admin.training.certificate') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'school'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => 'My training',
        ])
    </a>
</div>
<div class="top-nav-item">
    <a href="{{ route('admin.training.calls.index') }}"
       class="top-nav-trigger {{ request()->routeIs('admin.training.calls.index', 'admin.training.calls.show', 'admin.training.calls.room', 'admin.training.calls.result') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'phone_in_talk'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => 'Call tests',
        ])
    </a>
</div>
@if(is_super_admin())
    <span class="top-nav-admin-divider" aria-hidden="true"></span>
    @php
        $trainingSetupActive = request()->routeIs(
            'admin.training.manage',
            'admin.training.manage.edit',
            'admin.training.manage.curriculum',
            'admin.training.assign',
            'admin.training.live',
            'admin.training.calls.manage',
            'admin.training.calls.manage.edit',
            'admin.training.calls.manage.assign'
        );
    @endphp
    <div class="top-nav-item">
        <button type="button" class="top-nav-trigger {{ $trainingSetupActive ? 'is-active' : '' }}">
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'tune'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Training setup',
            ])
            <span class="material-icons expand-more-icon">expand_more</span>
        </button>
        <div class="top-nav-dropdown top-nav-dropdown--menu">
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.training.manage'),
                'label' => 'Programs',
                'active' => request()->routeIs('admin.training.manage', 'admin.training.manage.edit', 'admin.training.manage.curriculum'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.training.assign'),
                'label' => 'Assign',
                'active' => request()->routeIs('admin.training.assign'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.training.live'),
                'label' => 'Live',
                'active' => request()->routeIs('admin.training.live'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.training.calls.manage'),
                'label' => 'Call tests',
                'active' => request()->routeIs('admin.training.calls.manage', 'admin.training.calls.manage.edit', 'admin.training.calls.manage.assign'),
            ])
        </div>
    </div>
@endif
