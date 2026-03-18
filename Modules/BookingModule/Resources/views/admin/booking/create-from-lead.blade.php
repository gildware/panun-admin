@extends('leadmanagement::admin.leads.layout-modal')

@section('title', translate('Add_New_Booking'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/plugins/select2/select2.min.css"/>
@endpush

@section('content')
    @include('bookingmodule::admin.booking.partials._create-form')
@endsection

