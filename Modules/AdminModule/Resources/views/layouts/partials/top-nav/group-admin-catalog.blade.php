@canany(['service_view', 'service_add', 'zone_add', 'zone_view', 'category_view', 'category_add'])
@php
    $groupActive = \App\Support\AdminNavRegistry::groupIsActive('catalog');
    $catalogNewServiceRequestsCount = Gate::check('service_view') ? (int) ($new_service_requests_count ?? 0) : 0;
@endphp
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'category'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Catalog'),
            'count' => $catalogNewServiceRequestsCount,
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['zone_add', 'zone_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.zone.create'),
                'label' => translate('Service Zones Setup'),
                'active' => request()->is('admin/zone/*'),
            ])
        @endcanany
        @canany(['category_view', 'service_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.catalog.view'),
                'label' => translate('View_Catalog'),
                'active' => request()->is('admin/catalog/view*'),
            ])
        @endcanany
        @canany(['category_add', 'category_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.category.create'),
                'label' => translate('Categories'),
                'active' => request()->is('admin/category/*'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.sub-category.create'),
                'label' => translate('Sub_Categories'),
                'active' => request()->is('admin/sub-category/*'),
            ])
        @endcanany
        @can('service_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.service.index'),
                'label' => translate('services'),
                'active' => request()->is('admin/service/list*') || request()->is('admin/service/edit*') || request()->is('admin/service/detail*'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.service.request.list'),
                'label' => translate('New_Service_Requests'),
                'active' => request()->is('admin/service/request/list*'),
                'count' => $new_service_requests_count ?? 0,
            ])
        @endcan
        @can('service_add')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.service.create'),
                'label' => translate('add_new_service'),
                'active' => request()->is('admin/service/create'),
            ])
        @endcan
    </div>
</div>
@endcanany
