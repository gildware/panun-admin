@extends('adminmodule::layouts.master')

@section('title',translate('provider_details'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-title-wrap mb-3">
                @include('providermanagement::admin.provider.partials.provider-status-header', ['provider' => $provider])
            </div>

            <div class="mb-3">
                <ul class="nav nav--tabs nav--tabs__style2">
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'overview' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=overview">{{ translate('Overview') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscribed_services' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscribed_services">{{ translate('Subscribed_Services') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bookings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bookings">{{ translate('Bookings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'payment' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=payment">{{ translate('Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'reviews' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=reviews">{{ translate('Reviews') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'performance' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=performance">{{ translate('Performance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'bank_information' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=bank_information">{{ translate('Bank_Information') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'serviceman_list' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=serviceman_list">{{ translate('Service_Man_List') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'subscription' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=subscription&provider_id={{ request()->id ?? request()->provider_id }}">{{ translate('Business Plan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $webPage == 'settings' ? 'active' : '' }}" href="{{ url()->current() }}?web_page=settings">{{ translate('Settings') }}</a>
                    </li>
                </ul>
            </div>

            <div class="card">
                <div class="border-bottom d-flex gap-3 flex-wrap justify-content-between align-items-center px-4 py-3">
                    <div class="d-flex gap-2 align-items-center">
                        <span class="material-symbols-outlined">account_balance</span>

                        <h3>{{translate('Bank_Information')}}</h3>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                    </div>
                </div>
                <div class="card-body p-30">
                    @php
                        $bankDetails = $provider->bank_details ?? collect();
                        if(($bankDetails instanceof \Illuminate\Support\Collection) && $bankDetails->isEmpty() && !empty($provider->bank_detail)){
                            $bankDetails = collect([$provider->bank_detail]);
                        }
                        $defaultBankDetail = $bankDetails instanceof \Illuminate\Support\Collection ? $bankDetails->first() : null;
                    @endphp

                    <div class="row g-3">
                        @forelse($bankDetails as $bankDetail)
                            <div class="col-md-6 col-lg-4">
                                <div class="card bank-info-card bg-bottom bg-contain bg-img h-100"
                                     style="background-image: url('{{asset('assets/admin-module')}}/img/media/bank-info-card-bg.png');">
                                    <div class="border-bottom p-3">
                                        <h4 class="fw-semibold">
                                            {{translate('Holder_Name')}}:
                                            <strong>{{Str::limit($bankDetail->acc_holder_name ?? translate('Unavailable'), 50)}}</strong>
                                        </h4>
                                    </div>

                                    <div class="card-body position-relative flex-wrap d-flex align-items-start justify-content-between gap-1">
                                        <ul class="list-unstyled d-flex flex-column gap-4 mb-0">
                                            <li>
                                                <h3 class="mb-2">{{translate('Bank_Name')}}:</h3>
                                                <div>{{ $bankDetail->bank_name ?? translate('Unavailable') }}</div>
                                            </li>
                                            <li>
                                                <h3 class="mb-2">{{translate('Account_Number')}}:</h3>
                                                <div>{{ $bankDetail->acc_no ?? translate('Unavailable') }}</div>
                                            </li>
                                            <li>
                                                <h3 class="mb-2">IFSC code:</h3>
                                                <div>{{ $bankDetail->routing_number ?? translate('Unavailable') }}</div>
                                            </li>
                                        </ul>

                                        <img width="78" height="53" class="bank-card-img position-static"
                                             src="{{asset('assets/admin-module')}}/img/media/bank-card.png" alt="">
                                    </div>

                                    <div class="p-3 pt-0">
                                        <button type="button"
                                                class="btn btn--primary w-100"
                                                data-bs-toggle="modal"
                                                data-bs-target="#updateBankInfo"
                                                data-bank-detail-id="{{ $bankDetail->id }}"
                                                data-bank-name="{{ $bankDetail->bank_name ?? '' }}"
                                                data-acc-no="{{ $bankDetail->acc_no ?? '' }}"
                                                data-acc-holder-name="{{ $bankDetail->acc_holder_name ?? '' }}"
                                                data-routing-number="{{ $bankDetail->routing_number ?? '' }}">
                                            {{ translate('Edit') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light border">
                                    {{ translate('No_data_found') }}
                                </div>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        <button type="button"
                                class="btn btn--primary"
                                data-bs-toggle="modal"
                                data-bs-target="#updateBankInfo"
                                data-bank-detail-id=""
                                data-bank-name=""
                                data-acc-no=""
                                data-acc-holder-name=""
                                data-routing-number="">
                            {{ translate('Add') }} {{ translate('Bank_Information') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateBankInfo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{route('admin.provider.account.update',[$provider->id])}}" method="post">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"
                            id="changeScheduleModalLabel">{{translate('Update_Account_Information')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="bank_detail_id" value="">
                        <div class="form-floating mb-30">
                            <input type="text" class="form-control" name="bank_name"
                                   value="{{ $defaultBankDetail?->bank_name ?? '' }}"
                                   placeholder="{{translate('Bank_Name')}}">
                            <label>{{translate('Bank_Name')}}</label>
                        </div>
                        <div class="form-floating mb-30">
                            <input type="text" class="form-control" name="acc_no"
                                   value="{{ $defaultBankDetail?->acc_no ?? '' }}"
                                   placeholder="{{translate('Acc_No')}}" required>
                            <label>{{translate('Acc._No.')}}</label>
                        </div>
                        <div class="form-floating mb-30">
                            <input type="text" class="form-control" name="acc_holder_name"
                                   value="{{ $defaultBankDetail?->acc_holder_name ?? '' }}"
                                   placeholder="{{translate('Acc._Holder_Name')}}" required>
                            <label>{{translate('Acc._Holder_Name')}}</label>
                        </div>

                        <div class="form-floating mb-30">
                            <input type="text" class="form-control" name="routing_number"
                                   value="{{ $defaultBankDetail?->routing_number ?? '' }}"
                                   placeholder="IFSC code" required>
                            <label>IFSC code</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--secondary"
                                data-bs-dismiss="modal">{{translate('Close')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('Submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            "use strict";

            const updateBankModal = document.getElementById('updateBankInfo');
            if (updateBankModal) {
                updateBankModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const bankDetailId = button.getAttribute('data-bank-detail-id') || '';
                    updateBankModal.querySelector('input[name="bank_detail_id"]').value = bankDetailId;

                    updateBankModal.querySelector('input[name="bank_name"]').value = button.getAttribute('data-bank-name') || '';
                    updateBankModal.querySelector('input[name="acc_no"]').value = button.getAttribute('data-acc-no') || '';
                    updateBankModal.querySelector('input[name="acc_holder_name"]').value = button.getAttribute('data-acc-holder-name') || '';
                    updateBankModal.querySelector('input[name="routing_number"]').value = button.getAttribute('data-routing-number') || '';
                });
            }
        </script>
    @endpush
@endsection
