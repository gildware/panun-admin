@extends('adminmodule::layouts.master')

@section('title', $workspace->displayName($employee))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="people-ws">
                <div class="people-ws-main">
                    @include('adminmodule::admin.people.hr._person')
                </div>
            </div>
            @include('adminmodule::admin.people._date_pop')
        </div>
    </div>
@endsection
