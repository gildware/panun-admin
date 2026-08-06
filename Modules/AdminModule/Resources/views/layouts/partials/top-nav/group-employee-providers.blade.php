@canany(['provider_view', 'onboarding_request_view'])
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('providers');
    $providersOnboardingCount = Gate::check('onboarding_request_view')
        ? (int) ($pending_providers ?? 0)
        : 0;
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'engineering'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Providers'),
            'count' => $providersOnboardingCount,
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('provider_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.list', ['status' => 'all']),
                'label' => translate('Provider_List'),
                'active' => request()->is('admin/provider/list') || request()->is('admin/provider/details*') || request()->is('admin/provider/edit*') || request()->is('admin/provider/collect-cash*'),
            ])
        @endcan
        @can('onboarding_request_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.provider.onboarding_request', ['status' => 'onboarding']),
                'label' => translate('Onboarding_Request'),
                'active' => request()->is('admin/provider/onboarding*'),
                'count' => $pending_providers ?? 0,
            ])
        @endcan
    </div>
</div>
@endcanany
