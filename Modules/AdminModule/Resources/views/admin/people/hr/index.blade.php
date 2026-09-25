@extends('adminmodule::layouts.new-master')

@section('title', 'People & HR')

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/people-workspace.css') }}?v={{ filemtime(public_path('assets/admin-module/css/people-workspace.css')) }}">
@endpush

@section('content')
<div class="main-content">
    <div class="container-fluid">
        @include('adminmodule::admin.people._open', [
            'desk' => 'hr',
            'section' => $section === 'person' ? 'people' : $section,
            'links' => [],
        ])

        @if($section === 'home')
            @include('adminmodule::admin.people.hr._home')
        @elseif($section === 'people')
            @include('adminmodule::admin.people.hr._people')
        @elseif($section === 'person')
            @include('adminmodule::admin.people.hr._person')
        @elseif($section === 'departments')
            @include('adminmodule::admin.people.hr._departments')
        @elseif($section === 'leave')
            @include('adminmodule::admin.people.hr._leave')
        @elseif($section === 'attendance')
            @include('adminmodule::admin.people.hr._attendance')
        @elseif($section === 'salary')
            @include('adminmodule::admin.people.hr._salary')
        @elseif($section === 'payroll')
            @include('adminmodule::admin.people.hr._payroll')
        @endif

        @include('adminmodule::admin.people._close')
        @if(in_array($section, ['salary', 'person'], true))
            @include('adminmodule::admin.people._date_pop')
        @endif
    </div>
</div>
@endsection
