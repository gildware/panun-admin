@php
    $showPeopleHr = auth()->check()
        && in_array(auth()->user()->user_type, ADMIN_USER_TYPES, true)
        && (auth()->user()->user_type === 'super-admin'
            || auth()->user()->roles()->where('role_name', 'like', '%hr%')->exists());
@endphp
@if($showPeopleHr)
    <div class="top-nav-item">
        <a href="{{ route('admin.hr.index') }}"
           class="top-nav-trigger {{ request()->is('admin/hr*') || request()->is('admin/people/holidays*') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'groups'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'People & HR',
            ])
        </a>
    </div>
@endif
