@can('employee_view')
    <div class="top-nav-item">
        <a href="{{ route('admin.employee.index') }}"
           class="top-nav-trigger {{ request()->is('admin/employee*') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'list'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Employee list',
            ])
        </a>
    </div>
@endcan
@canany(['role_view', 'role_add'])
    <div class="top-nav-item">
        <a href="{{ route('admin.role.index') }}"
           class="top-nav-trigger {{ request()->is('admin/role*') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'admin_panel_settings'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Roles and Permission',
            ])
        </a>
    </div>
@endcanany
