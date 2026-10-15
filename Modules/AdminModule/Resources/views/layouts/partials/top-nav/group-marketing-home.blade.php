@php($marketingActive = request()->routeIs('admin.marketing.*') || \App\Support\AdminMarketingRegistry::isMarketingPage())
<div class="top-nav-item">
    <a href="{{ route('admin.marketing.index') }}"
       class="top-nav-trigger {{ $marketingActive ? 'active-menu' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'campaign'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Marketing'),
        ])
    </a>
</div>
