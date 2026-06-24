@extends('adminmodule::layouts.new-master')

@section('title', translate('Add_Outbound_Enquiry'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-3">
                        <h2 class="page-title">{{ translate('Add_Outbound_Enquiry') }}</h2>
                        <a href="{{ route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.outbound-enquiry.store') }}" method="post">
                                @csrf

                                @include('leadmanagement::admin.outbound-enquiries.partials._form_fields', [
                                    'formPrefix' => 'outbound-create',
                                    'remarksRows' => 4,
                                ])

                                <div class="d-flex justify-content-end gap-20 mt-4">
                                    <a href="{{ route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
                                        {{ translate('Cancel') }}
                                    </a>
                                    <button class="btn btn--primary" type="submit">
                                        {{ translate('Submit') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@include('leadmanagement::admin.outbound-enquiries.partials._form_script')
