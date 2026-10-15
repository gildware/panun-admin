@can('people_hr')
    @php
        $hrSection = (string) request()->route('section', '');
    @endphp
    <div class="top-nav-item">
        <a href="{{ route('admin.hr.attendance') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.hr.attendance') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'fact_check'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Attendance',
            ])
        </a>
    </div>
    <div class="top-nav-item">
        <a href="{{ route('admin.people.holidays') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.people.holidays') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'calendar_month'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Holidays',
            ])
        </a>
    </div>
    <div class="top-nav-item">
        <a href="{{ route('admin.hr.index', ['section' => 'leave', 'tab' => 'types']) }}"
           class="top-nav-trigger {{ request()->routeIs('admin.hr.index') && $hrSection === 'leave' ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'event_busy'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Leaves',
            ])
        </a>
    </div>
    <div class="top-nav-item">
        <a href="{{ route('admin.hr.index', ['section' => 'departments']) }}"
           class="top-nav-trigger {{ request()->routeIs('admin.hr.index') && $hrSection === 'departments' ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'account_tree'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Departments',
            ])
        </a>
    </div>
    <div class="top-nav-item">
        <a href="{{ route('admin.hr.index', ['section' => 'configuration']) }}"
           class="top-nav-trigger {{ request()->routeIs('admin.hr.index') && $hrSection === 'configuration' ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'tune'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Configuration',
            ])
        </a>
    </div>
@endcan
