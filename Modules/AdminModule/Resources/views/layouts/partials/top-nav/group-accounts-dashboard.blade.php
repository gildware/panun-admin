<div class="top-nav-item">
    <a href="{{ route('admin.dashboard.finance') }}"
       class="top-nav-trigger {{ request()->is('admin/dashboard/finance') ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'dashboard'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('dashboard'),
        ])
    </a>
</div>
