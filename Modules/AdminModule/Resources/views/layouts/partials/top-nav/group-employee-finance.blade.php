@canany(['transaction_view', 'ledger_view', 'withdraw_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('finance'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        @include('adminmodule::layouts.partials.top-nav._employee-nav-icon', ['icon' => 'payments'])
        @include('adminmodule::layouts.partials.top-nav._employee-nav-label', [
            'label' => translate('Finance'),
        ])
        <span class="material-icons expand-more-icon">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @can('ledger_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.ledger.index'),
                'label' => translate('Ledger'),
                'active' => request()->is('admin/ledger*'),
            ])
        @endcan
        @can('transaction_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.transaction.list', ['trx_type' => 'all']),
                'label' => translate('Transactions'),
                'active' => request()->is('admin/transaction/list*'),
            ])
        @endcan
        @canany(['transaction_view', 'ledger_view'])
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.transaction.pending_provider_balances.index'),
                'label' => translate('Pending_provider_balances'),
                'active' => request()->is('admin/transaction/pending-provider-balances*'),
            ])
        @endcanany
        @can('withdraw_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.withdraw.request.list', ['status' => 'all']),
                'label' => translate('Withdraw Requests'),
                'active' => request()->is('admin/withdraw/request*'),
            ])
        @endcan
    </div>
</div>
@endcanany
