@extends('adminmodule::layouts.new-master')

@php
    $pageTitles = [
        'people' => 'People',
        'person' => 'People',
        'departments' => 'Departments',
        'leave' => 'Leaves',
        'attendance' => 'Attendance',
        'salary' => 'Salary',
        'payroll' => 'Payroll',
        'configuration' => 'Configuration',
    ];
@endphp

@section('title', $pageTitles[$section] ?? 'People & HR')

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
        @elseif($section === 'configuration')
            @include('adminmodule::admin.people.hr._configuration')
        @endif

        @include('adminmodule::admin.people._close')
        @if(in_array($section, ['salary', 'person', 'configuration'], true))
            @include('adminmodule::admin.people._date_pop')
        @endif
    </div>
</div>
@endsection
