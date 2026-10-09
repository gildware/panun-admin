@if(admin_workspace() === 'operations')
    <div class="top-nav-item">
        <a href="{{ route('admin.dashboard') }}" class="top-nav-trigger {{ request()->is('admin/dashboard') && ! request()->is('admin/dashboard/*') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'dashboard'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => translate('dashboard'),
            ])
        </a>
    </div>
    @include('adminmodule::layouts.partials.top-nav.group-employee-leads')
    @include('adminmodule::layouts.partials.top-nav.group-hunting-board')
    @include('adminmodule::layouts.partials.top-nav.group-employee-bookings')
    @include('adminmodule::layouts.partials.top-nav.group-employee-task-board')
    @include('adminmodule::layouts.partials.top-nav.group-employee-progress-report')
    @include('adminmodule::layouts.partials.top-nav.group-employee-customers')
    @include('adminmodule::layouts.partials.top-nav.group-employee-providers')
    @include('adminmodule::layouts.partials.top-nav.group-employee-catalog')
@endif

@if(admin_workspace() === 'hr')
    @include('adminmodule::layouts.partials.top-nav.group-hr-dashboard')
    @include('adminmodule::layouts.partials.top-nav.group-people-workspace')
    @include('adminmodule::layouts.partials.top-nav.group-people-approvals')
    @include('adminmodule::layouts.partials.top-nav.group-people-hr')
    @include('adminmodule::layouts.partials.top-nav.group-people-staff')
@endif

@if(admin_workspace() === 'training')
    @include('adminmodule::layouts.partials.top-nav.group-training-dashboard')
    @include('adminmodule::layouts.partials.top-nav.group-employee-process-guides')
@endif

@if(admin_workspace() === 'marketing')
    @include('adminmodule::layouts.partials.top-nav.group-marketing-home')
@endif

@if(admin_workspace() === 'settings')
    @include('adminmodule::layouts.partials.top-nav.group-settings-home')
@endif

@if(admin_workspace() === 'accounts')
    @include('adminmodule::layouts.partials.top-nav.group-accounts-pay')
    @include('adminmodule::layouts.partials.top-nav.group-employee-finance')
@endif
