@extends('adminmodule::layouts.master')

@section('title', translate('Wallet'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-4">
                @include('customermodule::admin.detail.partials.page-header', ['customer' => $customer])
            </div>

            @include('customermodule::admin.detail.partials.sub-nav', ['webPage' => $webPage ?? 'wallet'])

            <div class="card">
                <div class="card-body p-30">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <h2 class="mb-0">{{ translate('Wallet') }}</h2>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @can('wallet_add')
                                <a href="{{ route('admin.customer.wallet.add-fund', ['user_id' => $customer->id]) }}"
                                   class="btn btn-outline--primary text-capitalize">
                                    {{ translate('Add_Fund') }}
                                </a>
                            @endcan
                            <div class="text-muted fs-12">{{ translate('Current_balance_and_wallet_usage') }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 statistics-card__total-earning h-100">
                                <h3>{{ translate('Current_Balance') }}</h3>
                                <h2>{{ with_currency_symbol($walletBalance) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 statistics-card__ongoing h-100">
                                <h3>{{ translate('Total_Credited') }}</h3>
                                <h2>{{ with_currency_symbol($totalCredit) }}</h2>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="statistics-card statistics-card__style2 statistics-card__canceled h-100">
                                <h3>{{ translate('Total_Used') }}</h3>
                                <h2>{{ with_currency_symbol($totalDebit) }}</h2>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom mb-10 gap-3">
                        <ul class="nav nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{ ($walletType ?? 'all') === 'all' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?web_page=wallet&wallet_type=all">{{ translate('All') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ ($walletType ?? '') === 'debit' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?web_page=wallet&wallet_type=debit">{{ translate('Used') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ ($walletType ?? '') === 'credit' ? 'active' : '' }}"
                                   href="{{ url()->current() }}?web_page=wallet&wallet_type=credit">{{ translate('Credited') }}</a>
                            </li>
                        </ul>
                        <div class="d-flex gap-2 fw-medium">
                            <span class="opacity-75">{{ translate('Total_Transactions') }}:</span>
                            <span class="title-color">{{ $walletTransactions->total() }}</span>
                        </div>
                    </div>

                    <h3 class="h5 mb-3">{{ translate('Wallet_Usage') }}</h3>

                    <div class="data-table-top d-flex flex-wrap gap-10 justify-content-between mb-3">
                        <form action="{{ url()->current() }}" class="search-form search-form_style-two" method="GET">
                            <input type="hidden" name="web_page" value="wallet">
                            <input type="hidden" name="wallet_type" value="{{ $walletType ?? 'all' }}">
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon">
                                    <span class="material-icons">search</span>
                                </span>
                                <input type="search" class="theme-input-style search-form__input"
                                       value="{{ $search ?? '' }}" name="search"
                                       placeholder="{{ translate('Search_transaction_or_booking') }}">
                            </div>
                            <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th>{{ translate('Transaction_ID') }}</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('Reference') }}</th>
                                <th>{{ translate('Transaction_Date') }}</th>
                                <th class="text-end">{{ translate('Debit') }}</th>
                                <th class="text-end">{{ translate('Credit') }}</th>
                                <th class="text-end">{{ translate('Balance') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($walletTransactions as $key => $transaction)
                                @php
                                    $walletReferenceNote = function_exists('sanitize_wallet_transaction_reference_note')
                                        ? sanitize_wallet_transaction_reference_note($transaction->reference_note)
                                        : $transaction->reference_note;
                                @endphp
                                <tr>
                                    <td>{{ $walletTransactions->firstItem() + $key }}</td>
                                    <td class="text-nowrap" title="{{ $transaction->id }}">
                                        <span class="font-monospace fz-12">{{ Str::limit($transaction->id, 13, '…') }}</span>
                                    </td>
                                    <td>
                                        {{ translate($transaction->trx_type) }}
                                        @if(($transaction->to_user_account ?? '') === 'balance_pending')
                                            <div class="fz-10 text-muted">{{ translate('balance_pending') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($walletReferenceNote)
                                            <span>{{ $walletReferenceNote }}</span>
                                        @elseif(isset($transaction->booking) && $transaction->booking?->readable_id)
                                            <a href="{{ route('admin.booking.details', [$transaction->booking->id, 'web_page' => 'details']) }}">
                                                {{ translate('Booking') }} #{{ $transaction->booking->readable_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ date('d-M-Y h:ia', strtotime($transaction->created_at)) }}</td>
                                    <td class="text-end {{ $transaction->debit > 0 ? 'text-danger' : 'text-muted' }}">
                                        @if($transaction->debit > 0)
                                            -{{ with_currency_symbol($transaction->debit) }}
                                        @else
                                            {{ with_currency_symbol($transaction->debit) }}
                                        @endif
                                    </td>
                                    <td class="text-end {{ $transaction->credit > 0 ? 'text-success' : 'text-muted' }}">
                                        @if($transaction->credit > 0)
                                            +{{ with_currency_symbol($transaction->credit) }}
                                        @else
                                            {{ with_currency_symbol($transaction->credit) }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">{{ with_currency_symbol($transaction->balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">{{ translate('No_wallet_transactions') }}</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($walletTransactions->hasPages())
                        <div class="d-flex justify-content-end">
                            {!! $walletTransactions->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
