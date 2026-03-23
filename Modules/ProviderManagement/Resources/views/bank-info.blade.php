@extends('providermanagement::layouts.master')

@section('title',translate('bank_Info'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3">
                        <h2 class="page-title">{{translate('Provider_Bank_Information')}}</h2>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-flex gap-2 align-items-center mb-4">
                                <img width="20" src="{{asset('/assets/admin-module/img/icons/card.png')}}"
                                     alt="">
                                <h5 class="mb-0">{{translate('Account_Details')}}</h5>
                                <span class="material-symbols-outlined" data-bs-toggle="tooltip"
                                      data-bs-placement="bottom" title="{{translate('Please update your account details with accurate information. This information will be used by the admin for processing withdrawal request transaction
')}}">info</span>
                            </div>

                            <div class="row g-3">
                                @forelse($provider->bank_details ?? [] as $bankDetail)
                                    <div class="col-md-6 col-xl-5">
                                        <div class="provider-bank-card d-flex justify-content-between gap-3 p-4 border align-items-start flex-wrap">
                                            <div class="">
                                                <div class="d-flex info gap-2 align-items-center mb-4">
                                                    <span class="material-icons">person</span>
                                                    {{translate('Holder Name')}}:
                                                    <strong>{{ $bankDetail->acc_holder_name ?? '' }}</strong>
                                                </div>

                                                <div class="d-flex flex-column info gap-2">
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <span class="min-w-100px">{{translate('Bank Name')}}</span>:
                                                        <span>{{ $bankDetail->bank_name ?? '' }}</span>
                                                    </div>

                                                    <div class="d-flex gap-2 align-items-center">
                                                        <span class="min-w-100px">{{ translate('Account_No') }}</span>:
                                                        <span>{{ $bankDetail->acc_no ?? '' }}</span>
                                                    </div>

                                                    <div class="d-flex gap-2 align-items-center">
                                                        <span class="min-w-100px">IFSC code</span>:
                                                        <span>{{ $bankDetail->routing_number ?? '' }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <button type="button"
                                                    class="btn btn-primary d-flex gap-2 bank-edit-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#exampleModal"
                                                    data-bank-detail-id="{{ $bankDetail->id }}"
                                                    data-bank-name="{{ $bankDetail->bank_name ?? '' }}"
                                                    data-acc-no="{{ $bankDetail->acc_no ?? '' }}"
                                                    data-acc-holder-name="{{ $bankDetail->acc_holder_name ?? '' }}"
                                                    data-routing-number="{{ $bankDetail->routing_number ?? '' }}">
                                                {{translate('edit')}}
                                                <span class="material-symbols-outlined m-0">edit</span>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="text-muted">{{ translate('No_data_found') }}</div>
                                    </div>
                                @endforelse
                            </div>

                            <div class="mt-4">
                                <button type="button"
                                        class="btn btn--primary bank-add-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#exampleModal"
                                        data-bank-detail-id=""
                                        data-bank-name=""
                                        data-acc-no=""
                                        data-acc-holder-name=""
                                        data-routing-number="">
                                    Add Bank Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">{{translate('general_information')}}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{route('provider.update_bank_info')}}" method="post"
                          enctype="multipart/form-data">
                        @csrf
                        @method('put')
                        <input type="hidden" name="bank_detail_id" value="">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-floating form-floating__icon mb-30">
                                    <input type="text" class="form-control" name="bank_name"
                                           placeholder="{{translate('Bank_Name')}}"
                                           value="" required>
                                    <label>{{translate('Bank_Name')}}</label>
                                    <span class="material-icons">account_balance</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating__icon mb-30">
                                    <input type="text" class="form-control" name="acc_no"
                                           placeholder="{{translate('Account_No')}}"
                                           value="" required>
                                    <label>{{translate('Account_No')}}</label>
                                    <span class="material-icons">pin</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating__icon mb-30">
                                    <input type="text" class="form-control" name="acc_holder_name"
                                           placeholder="{{translate('A/C_Holder_Name')}}"
                                           value="" required>
                                    <label>{{translate('A/C_Holder_Name')}}</label>
                                    <span class="material-icons">account_circle</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating form-floating__icon mb-30">
                                    <input type="text" class="form-control" name="routing_number"
                                           placeholder="IFSC code"
                                           value="" required>
                                    <label>IFSC code</label>
                                    <span class="material-icons">monitoring</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-4 flex-wrap justify-content-end">
                            <button type="reset" class="btn btn--secondary">{{translate('Reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('Submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalEl = document.getElementById('exampleModal');
                if (!modalEl) return;

                modalEl.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const bankDetailId = button?.getAttribute('data-bank-detail-id') || '';
                    const bankName = button?.getAttribute('data-bank-name') || '';
                    const accNo = button?.getAttribute('data-acc-no') || '';
                    const accHolderName = button?.getAttribute('data-acc-holder-name') || '';
                    const routingNumber = button?.getAttribute('data-routing-number') || '';

                    modalEl.querySelector('input[name="bank_detail_id"]').value = bankDetailId;
                    modalEl.querySelector('input[name="bank_name"]').value = bankName;
                    modalEl.querySelector('input[name="acc_no"]').value = accNo;
                    modalEl.querySelector('input[name="acc_holder_name"]').value = accHolderName;
                    modalEl.querySelector('input[name="routing_number"]').value = routingNumber;
                });
            });
        </script>
    @endpush
@endsection
