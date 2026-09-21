@php
    $groupActive = request()->is('admin/dashboard') || request()->is('admin/dashboard/*');
    $workActive = request()->is('admin/dashboard') && ! request()->is('admin/dashboard/*');
    $operationsActive = request()->is('admin/dashboard/operations');
    $financeActive = request()->is('admin/dashboard/finance');
    $operatingSystemActive = request()->is('admin/dashboard/operating-system*');
    $businessSystemActive = request()->is('admin/dashboard/business-system*');
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'dashboard'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('dashboard'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.dashboard'),
            'label' => translate('Work'),
            'active' => $workActive,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.dashboard.operations'),
            'label' => translate('Operations'),
            'active' => $operationsActive,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.dashboard.finance'),
            'label' => translate('Finance'),
            'active' => $financeActive,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.dashboard.operating-system'),
            'label' => translate('Operating_System'),
            'active' => $operatingSystemActive,
            'fullPage' => true,
        ])
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.dashboard.business-system'),
            'label' => translate('Business_System'),
            'active' => $businessSystemActive,
            'fullPage' => true,
        ])
    </div>
</div>
