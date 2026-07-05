@canany(['role_view', 'role_add', 'employee_add', 'employee_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('team'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Team') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['role_view', 'role_add'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.role.index'),
                'label' => translate('Employee Role Setup'),
                'active' => request()->is('admin/role/*'),
            ])
        @endcanany

        @can('employee_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.employee.index'),
                'label' => translate('employee_list'),
                'active' => request()->is('admin/employee/list') || request()->is('admin/employee/edit/*'),
            ])
        @endcan

        @can('employee_add')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.employee.create'),
                'label' => translate('add_new_employee'),
                'active' => request()->is('admin/employee/create'),
            ])
        @endcan
    </div>
</div>
@endcanany
