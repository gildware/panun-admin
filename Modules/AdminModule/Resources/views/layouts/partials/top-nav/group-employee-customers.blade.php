@canany(['customer_view', 'customer_add'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('customers'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'groups'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Customers'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('customer_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.index'),
                'label' => translate('customer_list'),
                'active' => (request()->is('admin/customer/list') || request()->is('admin/customer/detail*') || request()->is('admin/customer/edit/*'))
                    && ! request()->is('admin/customer/create'),
            ])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer-cart.index'),
                'label' => translate('Customer_Cart'),
                'active' => request()->is('admin/customer-cart*'),
                'count' => $customer_cart_not_contacted_count ?? 0,
            ])
        @endcan
        @can('customer_add')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.customer.create'),
                'label' => translate('add_new_customer'),
                'active' => request()->is('admin/customer/create'),
            ])
        @endcan
    </div>
</div>
@endcanany
