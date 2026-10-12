@extends('adminmodule::layouts.new-master')

@section('title', 'New call test')

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    @include('adminmodule::admin.training.calls._styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">New call test</h2>
                    <div class="trn-meta mt-1">Set the call, choose one scenario, and add the skills this test should score.</div>
                </div>
                <div class="lms-pagehead-actions">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.manage') }}">All call tests</a>
                </div>
            </div>

            @if($errors->any())
                <div class="card">
                    <div class="card-body trn-meta" style="color:#b91c1c">{{ $errors->first() }}</div>
                </div>
            @endif

            @include('adminmodule::admin.training.calls.manage._form', [
                'action' => route('admin.training.calls.manage.store'),
                'method' => null,
                'submitLabel' => 'Create call test',
            ])
        </div>
    </div>
@endsection
