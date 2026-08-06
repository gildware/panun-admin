@if(count(\App\Support\AdminMarketingRegistry::visibleSections()) > 0)
@php($marketingActive = request()->routeIs('admin.marketing.*') || \App\Support\AdminNavRegistry::groupIsActive('marketing'))
<div class="top-nav-item top-nav-item--module-link">
    <a href="{{ route('admin.marketing.index') }}"
       class="top-nav-trigger top-nav-trigger--module-link {{ $marketingActive ? 'active-menu is-active' : '' }}"
       @if(admin_uses_partial_nav()) data-turbo-frame="admin-main" data-turbo-action="advance" @endif>
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'campaign'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Marketing'),
        ])
    </a>
</div>
@endif
