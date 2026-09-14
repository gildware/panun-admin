@extends('adminmodule::layouts.new-master')

@section('title', translate('Edit_Outbound_Enquiry'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-wrap mb-3 d-flex justify-content-between flex-wrap align-items-center gap-3">
                        <h2 class="page-title">{{ translate('Edit_Outbound_Enquiry') }}</h2>
                        <a href="{{ !empty($fromLead) && $enquiry->lead_id ? route('admin.lead.show', $enquiry->lead_id) : route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
                            {{ translate('Back') }}
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-body p-30">
                            <form action="{{ route('admin.lead.outbound-enquiry.update', $enquiry) }}" method="post" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @if(!empty($fromLead) || old('from_lead'))
                                    <input type="hidden" name="from_lead" value="1">
                                @endif

                                @include('leadmanagement::admin.outbound-enquiries.partials._form_fields', [
                                    'formPrefix' => 'outbound-edit',
                                    'remarksRows' => 4,
                                    'enquiry' => $enquiry,
                                ])

                                <div class="d-flex justify-content-end gap-20 mt-4">
                                    <a href="{{ !empty($fromLead) && $enquiry->lead_id ? route('admin.lead.show', $enquiry->lead_id) : route('admin.lead.outbound-enquiry.index') }}" class="btn btn--secondary">
                                        {{ translate('Cancel') }}
                                    </a>
                                    <button class="btn btn--primary" type="submit">
                                        {{ translate('Update') }}
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
