@php
    $workActive = request()->is('admin/dashboard') && ! request()->is('admin/dashboard/*');
    $operationsActive = request()->is('admin/dashboard/operations');
    $groupActive = $workActive || $operationsActive;
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
    </div>
</div>
