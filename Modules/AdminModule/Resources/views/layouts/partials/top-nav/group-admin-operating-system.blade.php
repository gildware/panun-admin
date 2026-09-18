@if(! is_admin_employee())
<div class="top-nav-item">
    <a href="{{ route('admin.dashboard.operating-system') }}"
       class="top-nav-trigger {{ request()->is('admin/dashboard/operating-system*') ? 'active-menu' : '' }}"
       data-turbo="false">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'account_tree'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Operating_System'),
        ])
    </a>
</div>
@endif
