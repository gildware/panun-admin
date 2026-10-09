@if(admin_workspace() === 'operations')
    @include('adminmodule::layouts.partials.top-nav.group-admin-dashboard')
    @include('adminmodule::layouts.partials.top-nav.group-admin-leads')
    @include('adminmodule::layouts.partials.top-nav.group-hunting-board')
    @include('adminmodule::layouts.partials.top-nav.group-admin-bookings')
    @include('adminmodule::layouts.partials.top-nav.group-employee-task-board')
    @include('adminmodule::layouts.partials.top-nav.group-employee-progress-report')
    @include('adminmodule::layouts.partials.top-nav.group-admin-customers')
    @include('adminmodule::layouts.partials.top-nav.group-admin-providers')
    @include('adminmodule::layouts.partials.top-nav.group-admin-catalog')
    @include('adminmodule::layouts.partials.top-nav.group-reports')
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
    @include('adminmodule::layouts.partials.top-nav.group-admin-business-system')
@endif

@if(admin_workspace() === 'marketing')
    @include('adminmodule::layouts.partials.top-nav.group-marketing-home')
@endif

@if(admin_workspace() === 'settings')
    @include('adminmodule::layouts.partials.top-nav.group-settings-home')
@endif

@if(admin_workspace() === 'accounts')
    @include('adminmodule::layouts.partials.top-nav.group-accounts-dashboard')
    @include('adminmodule::layouts.partials.top-nav.group-accounts-pay')
    @include('adminmodule::layouts.partials.top-nav.group-finance')
@endif
