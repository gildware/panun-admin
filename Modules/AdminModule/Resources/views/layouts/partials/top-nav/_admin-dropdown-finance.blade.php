@canany(['transaction_view', 'ledger_view', 'wallet_view', 'point_view', 'withdraw_view'])
    @include('adminmodule::layouts.partials.top-nav._section', ['label' => translate('Finance')])

    @can('transaction_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.transaction.list', ['trx_type' => 'all']),
            'label' => translate('All Transactions'),
            'active' => request()->is('admin/transaction/list*'),
        ])
    @endcan
    @can('ledger_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.ledger.index'),
            'label' => translate('Ledger'),
            'active' => request()->is('admin/ledger*'),
        ])
    @endcan
    @can('wallet_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.customer.wallet.report'),
            'label' => translate('Wallet Transactions'),
            'active' => request()->is('admin/customer/wallet/report'),
        ])
    @endcan
    @can('point_view')
        @include('adminmodule::layouts.partials.top-nav._link', [
            'href' => route('admin.customer.loyalty-point.report'),
            'label' => translate('Loyalty Points Transactions'),
            'active' => request()->is('admin/customer/loyalty-point/report'),
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
@endcanany
