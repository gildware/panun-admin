@can('people_hr')
    <div class="top-nav-item">
        <a href="{{ route('admin.accounts.attendance') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.accounts.attendance') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'fact_check'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Attendance',
            ])
        </a>
    </div>
@endcan
@canany(['people_hr', 'ledger_view'])
    <div class="top-nav-item">
        <a href="{{ route('admin.accounts.payroll') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.accounts.payroll') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'receipt_long'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Payroll',
            ])
        </a>
    </div>
@endcanany
@can('people_hr')
    <div class="top-nav-item">
        <a href="{{ route('admin.accounts.salary') }}"
           class="top-nav-trigger {{ request()->routeIs('admin.accounts.salary') ? 'active-menu' : '' }}"
           @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
            @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'account_balance_wallet'])
            @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
                'label' => 'Salary',
            ])
        </a>
    </div>
@endcan
