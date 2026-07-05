@canany(['transaction_view', 'ledger_view', 'report_view'])
@php($groupActive = \App\Support\AdminNavRegistry::groupIsActive('finance'))
<div class="top-nav-item">
    <button type="button" class="top-nav-trigger {{ $groupActive ? 'is-active' : '' }}">
        {{ translate('Finance') }} <span class="material-icons">expand_more</span>
    </button>
    <div class="top-nav-dropdown top-nav-dropdown--menu">
        @canany(['transaction_view', 'ledger_view'])
            @can('transaction_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.transaction.list', ['trx_type' => 'all']),
                    'label' => translate('All Transactions'),
                    'active' => request()->is('admin/transaction/list*'),
                ])
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.transaction.razorpay_webhooks.index'),
                    'label' => translate('Razorpay_webhook_logs'),
                    'active' => request()->is('admin/transaction/razorpay-webhooks*'),
                ])
            @endcan
            @can('ledger_view')
                @include('adminmodule::layouts.partials.top-nav._link', [
                    'href' => route('admin.ledger.index'),
                    'label' => translate('Ledger'),
                    'active' => request()->is('admin/ledger*'),
                ])
            @endcan
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.transaction.pending_provider_balances.index'),
                'label' => translate('Pending_provider_balances'),
                'active' => request()->is('admin/transaction/pending-provider-balances'),
            ])
        @endcanany

        @can('report_view')
            @include('adminmodule::layouts.partials.top-nav._link', [
                'href' => route('admin.report.transaction', ['transaction_type' => 'all']),
                'label' => translate('Transaction Reports'),
                'active' => request()->is('admin/report/transaction*'),
            ])
        @endcan
    </div>
</div>
@endcanany
