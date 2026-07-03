@extends('adminmodule::layouts.master')

@section('title',translate('Customer_Configuration'))

@push('css_or_js')

@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Customer_Settings')}}</h2>
                    </div>

                    <div class="mb-3">
                        <ul class="nav nav--tabs nav--tabs__style2">
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=loyalty_point"
                                   class="nav-link {{$web_page=='loyalty_point'?'active':''}}">
                                    {{translate('loyalty_point')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=wallet"
                                   class="nav-link {{$web_page=='wallet'?'active':''}}">
                                    {{translate('wallet')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=welcome_bonus"
                                   class="nav-link {{$web_page=='welcome_bonus'?'active':''}}">
                                    {{translate('Welcome Bonus')}}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{url()->current()}}?web_page=referral_earning"
                                   class="nav-link {{$web_page=='referral_earning'?'active':''}}">
                                    {{translate('referral_earning')}}
                                </a>
                            </li>
                        </ul>
                    </div>

                    @if($web_page=='loyalty_point')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='loyalty_point'?'active show':''}}">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form
                                            action="{{route('admin.customer.settings', ['web_page' => 'loyalty_point'])}}"
                                            method="POST" id="landing-info-update-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="discount-type">
                                                <div class="row">
                                                    <div class="col-12 d-flex justify-content-start mb-3">
                                                        @php($value=$data_values->where('key_name','customer_loyalty_point')->first())
                                                        <h4>{{translate('Customer Loyalty Point')}}</h4>
                                                        <label class="switcher mx-2">
                                                            <input class="switcher_input" type="checkbox" value="1"
                                                                   name="customer_loyalty_point"
                                                                {{isset($value) && $value->live_values == '1' ? 'checked' : ''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>

                                                    <div class="col-12 row">
                                                        <div class="col-md-4 col-12 mb-30">
                                                            @php($value=$data_values->where('key_name','loyalty_point_percentage_per_booking')->first())
                                                            <label
                                                                class="mb-1">{{translate('Percentage Of Loyalty Point per Booking Amount')}}
                                                                <i class="material-icons" data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="{{translate('On every booking this percent of amount will be added as loyalty point on customer account')}}">info</i>
                                                            </label>
                                                            <input type="number" class="form-control"
                                                                   name="loyalty_point_percentage_per_booking"
                                                                   min="0" max="100" step="any"
                                                                   value="{{$value->live_values??''}}">
                                                        </div>
                                                        @php($value=$data_values->where('key_name','loyalty_point_value_per_currency_unit')->first())
                                                        <div class="col-md-4 col-12 mb-30">
                                                            <label
                                                                class="mb-1">1 {{currency_code()}} {{translate('equal to how many loyalty points')}}
                                                                ?</label>
                                                            <input type="number" class="form-control"
                                                                   name="loyalty_point_value_per_currency_unit"
                                                                   step="any"
                                                                   min="0" value="{{$value->live_values??''}}">
                                                        </div>
                                                        <div class="col-md-4 col-12 mb-30">
                                                            @php($value=$data_values->where('key_name','min_loyalty_point_to_transfer')->first())
                                                            <label
                                                                class="mb-1">{{translate('Minimum Loyalty Points To Transfer Into Wallet')}}</label>
                                                            <input type="number" class="form-control"
                                                                   name="min_loyalty_point_to_transfer" step="any"
                                                                   min="0" value="{{$value->live_values??''}}">
                                                        </div>
                                                        @php($value=$data_values->where('key_name','min_grand_total_for_loyalty_point')->first())
                                                        <div class="col-md-4 col-12 mb-30">
                                                            <label class="mb-1">
                                                                {{translate('Minimum grand total to receive loyalty points')}}
                                                                <i class="material-icons" data-bs-toggle="tooltip"
                                                                   data-bs-placement="top"
                                                                   title="{{translate('If the final grand total is below this amount, the customer will not receive loyalty points')}}">info</i>
                                                            </label>
                                                            <input type="number" class="form-control"
                                                                   name="min_grand_total_for_loyalty_point" step="any"
                                                                   min="0" value="{{$value->live_values ?? 0}}">
                                                        </div>
                                                    </div>

                                                    <div class="col-12 mb-30">
                                                        <label class="mb-2 d-block fw-semibold">
                                                            {{translate('Completed booking types for loyalty points')}}
                                                            <i class="material-icons" data-bs-toggle="tooltip"
                                                               data-bs-placement="top"
                                                               title="{{translate('Choose which completed booking settlement types earn loyalty points')}}">info</i>
                                                        </label>
                                                        <div class="d-flex flex-wrap gap-4 mb-3">
                                                            <label class="d-flex align-items-center gap-2 mb-0">
                                                                <input type="radio" name="loyalty_point_completion_outcome_filter_mode"
                                                                       value="include" {{ ($loyaltyOutcomeFilterMode ?? 'include') === 'include' ? 'checked' : '' }}>
                                                                {{translate('Include only selected types')}}
                                                            </label>
                                                            <label class="d-flex align-items-center gap-2 mb-0">
                                                                <input type="radio" name="loyalty_point_completion_outcome_filter_mode"
                                                                       value="exclude" {{ ($loyaltyOutcomeFilterMode ?? 'include') === 'exclude' ? 'checked' : '' }}>
                                                                {{translate('Exclude selected types')}}
                                                            </label>
                                                        </div>
                                                        <div class="row g-3">
                                                            @foreach(($loyaltyCompletionOutcomeOptions ?? []) as $outcomeKey => $outcomeLabel)
                                                                <div class="col-md-6 col-lg-4">
                                                                    <div class="custom__checkbox d-flex align-items-start gap-2">
                                                                        <input type="checkbox"
                                                                               id="loyalty_outcome_{{ $outcomeKey }}"
                                                                               name="loyalty_point_completion_outcomes[]"
                                                                               value="{{ $outcomeKey }}"
                                                                               {{ in_array($outcomeKey, $loyaltySelectedOutcomes ?? [], true) ? 'checked' : '' }}>
                                                                        <label for="loyalty_outcome_{{ $outcomeKey }}" class="m-0">
                                                                            {{ $outcomeLabel }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <p class="text-muted fz-12 mt-2 mb-0">
                                                            {{translate('Leave all unchecked to allow every completed booking type. Standard applies when no special settlement was used.')}}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset"
                                                        class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit"
                                                        class="btn btn--primary">{{translate('update')}}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='wallet')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='wallet'?'active show':''}}">
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form action="{{route('admin.customer.settings', ['web_page' => 'wallet'])}}"
                                              method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-start mb-3">
                                                    @php($value=$data_values->where('key_name','customer_wallet')->first())
                                                    <h4>{{translate('Customer Wallet')}}</h4>
                                                    <label class="switcher mx-2">
                                                        <input class="switcher_input" type="checkbox" value="1"
                                                               name="customer_wallet"
                                                            {{isset($value) && $value->live_values == '1' ? 'checked' : ''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>
                                                @php($value=$data_values->where('key_name','max_wallet_spend_per_transaction')->first())
                                                <div class="col-md-6 col-12 mb-30">
                                                    <label class="mb-1">
                                                        {{translate('Maximum wallet spend per transaction')}}
                                                        <i class="material-icons" data-bs-toggle="tooltip"
                                                           data-bs-placement="top"
                                                           title="{{translate('Customers cannot pay more than this amount from wallet in a single transaction. Set 0 for no limit.')}}">info</i>
                                                    </label>
                                                    <input type="number" class="form-control"
                                                           name="max_wallet_spend_per_transaction" step="any"
                                                           min="0" value="{{ $value->live_values ?? 0 }}">
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset"
                                                        class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit"
                                                        class="btn btn--primary">{{translate('update')}}</button>
                                            </div>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='welcome_bonus')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='welcome_bonus'?'active show':''}}">
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('admin.customer.welcome-bonus.report') }}"
                                       class="btn btn-outline--primary btn-sm d-inline-flex align-items-center gap-1">
                                        <span class="material-icons fz-16">analytics</span>
                                        {{ translate('View_Welcome_Bonus_Report') }}
                                    </a>
                                </div>
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form action="{{route('admin.customer.settings', ['web_page' => 'welcome_bonus'])}}"
                                              method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-start align-items-center mb-3">
                                                    @php($welcomeBonus=$data_values->where('key_name','customer_welcome_bonus')->first())
                                                    <div>
                                                        <h4 class="mb-1">{{translate('Welcome Bonus')}}</h4>
                                                        <p class="text-muted fz-12 mb-0">
                                                            {{translate('Credit new app customers with a one-time wallet bonus when they register')}}
                                                        </p>
                                                    </div>
                                                    <label class="switcher mx-3">
                                                        <input class="switcher_input" type="checkbox" value="1"
                                                               name="customer_welcome_bonus"
                                                            {{isset($welcomeBonus) && $welcomeBonus->live_values == '1' ? 'checked' : ''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>

                                                <div class="col-md-6 col-12 mb-30">
                                                    @php($welcomeBonusAmount=$data_values->where('key_name','customer_welcome_bonus_amount')->first())
                                                    <label class="mb-1">
                                                        {{translate('Welcome Bonus Amount')}} ({{currency_symbol()}})
                                                        <i class="material-icons" data-bs-toggle="tooltip"
                                                           data-bs-placement="top"
                                                           title="{{translate('Amount credited to a new customer wallet on first app registration')}}">info</i>
                                                    </label>
                                                    <input type="number" class="form-control"
                                                           name="customer_welcome_bonus_amount" step="any"
                                                           min="0" value="{{ $welcomeBonusAmount->live_values ?? 0 }}">
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset"
                                                        class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit"
                                                        class="btn btn--primary">{{translate('update')}}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($web_page=='referral_earning')
                        <div class="tab-content">
                            <div class="tab-pane fade {{$web_page=='referral_earning'?'active show':''}}">
                                <div class="d-flex justify-content-end mb-3">
                                    <a href="{{ route('admin.customer.referral-earning.report') }}"
                                       class="btn btn-outline--primary btn-sm d-inline-flex align-items-center gap-1">
                                        <span class="material-icons fz-16">analytics</span>
                                        {{ translate('View_Referral_Report') }}
                                    </a>
                                </div>
                                <div class="card">
                                    <div class="card-body p-30">
                                        <form
                                            action="{{route('admin.customer.settings', ['web_page' => 'referral_earning'])}}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')

                                            <div class="row">
                                                <div class="col-12 d-flex justify-content-start mb-3">
                                                    @php($value=$data_values->where('key_name','customer_referral_earning')->first())
                                                    <h4>{{translate('Customer Referral Earning')}}</h4>
                                                    <label class="switcher mx-2">
                                                        <input class="switcher_input" type="checkbox" value="1"
                                                               name="customer_referral_earning"
                                                            {{isset($value) && $value->live_values == '1' ? 'checked' : ''}}
                                                        >
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </div>

                                                <div class="col-12 row">
                                                    @php($value=$data_values->where('key_name','referral_value_per_currency_unit')->first())
                                                    <div class="col-md-12 mb-30">
                                                        <label
                                                            class="mb-1">{{translate('One Referrer Equal To How Much') . ' ' . currency_code() . '?'}}</label>
                                                        <input type="number" class="form-control"
                                                               name="referral_value_per_currency_unit" step="any"
                                                               min="0" value="{{$value->live_values??''}}">
                                                    </div>

                                                    @php($shareTemplate=$data_values->where('key_name','referral_share_message_template')->first())
                                                    <div class="col-md-12 mb-30">
                                                        <label class="mb-1">
                                                            {{translate('Referral Share Message Template')}}
                                                            <i class="material-icons" data-bs-toggle="tooltip"
                                                               data-bs-placement="top"
                                                               title="{{translate('This message is shared when a customer taps Share on Refer & Earn. Use placeholders: {CODE}, {APP_NAME}, {ANDROID_APP_URL}, {IOS_APP_URL}')}}">info</i>
                                                        </label>
                                                        <textarea class="form-control" name="referral_share_message_template"
                                                                  rows="6"
                                                                  placeholder="{{translate('Hi! Please use this {CODE} at time of registration to book services from {APP_NAME}.')}}&#10;{{translate('Download Android app: {ANDROID_APP_URL}')}}&#10;{{translate('Download iOS app: {IOS_APP_URL}')}}">{{$shareTemplate->live_values ?? ''}}</textarea>
                                                        <small class="text-muted d-block mt-2">
                                                            {{translate('Available placeholders')}}:
                                                            <code>{CODE}</code>,
                                                            <code>{APP_NAME}</code>,
                                                            <code>{ANDROID_APP_URL}</code>,
                                                            <code>{IOS_APP_URL}</code>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 justify-content-end">
                                                <button type="reset"
                                                        class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit"
                                                        class="btn btn--primary">{{translate('update')}}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
@endsection
