@if(! is_admin_employee())
<div class="top-nav-item">
    <a href="{{ route('admin.dashboard.business-system') }}"
       class="top-nav-trigger {{ request()->is('admin/dashboard/business-system*') ? 'active-menu' : '' }}"
       data-turbo="false">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'lan'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Business_System'),
        ])
    </a>
</div>
@endif
