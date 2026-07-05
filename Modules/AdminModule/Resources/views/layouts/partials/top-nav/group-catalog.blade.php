@canany(['service_view', 'service_add', 'zone_add', 'zone_view', 'category_view', 'category_add'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('catalog'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Catalog') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['zone_add', 'zone_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.zone.create'),
                'label' => translate('Service Zones Setup'),
                'active' => request()->is('admin/zone/*'),
            ])
        @endcanany

        @canany(['category_add', 'category_view'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Categories')])
            @canany(['category_view', 'service_view'])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.catalog.view'),
                    'label' => translate('View_Catalog'),
                    'active' => request()->is('admin/catalog/view*'),
                ])
            @endcanany
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.category.create'),
                'label' => translate('Category Setup'),
                'active' => request()->is('admin/category/*'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.sub-category.create'),
                'label' => translate('Sub Category Setup'),
                'active' => request()->is('admin/sub-category/*'),
            ])
        @endcanany

        @canany(['service_view', 'service_add'])
            @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('services')])
            @can('service_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.service.index'),
                    'label' => translate('service_list'),
                    'active' => request()->is('admin/service/list*') || request()->is('admin/service/edit*') || request()->is('admin/service/details*'),
                ])
            @endcan
            @can('service_add')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.service.create'),
                    'label' => translate('add_new_service'),
                    'active' => request()->is('admin/service/create'),
                ])
            @endcan
            @can('service_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.service.request.list'),
                    'label' => translate('New Service Requests'),
                    'active' => request()->is('admin/service/request/list*'),
                ])
            @endcan
        @endcanany
    </div>
</div>
@endcanany
