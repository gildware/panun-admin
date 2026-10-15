@extends('adminmodule::layouts.new-master')

@section('title', $test->name)

@push('css_or_js')
    @include('adminmodule::admin.training._compact')
    @include('adminmodule::admin.training.calls._styles')
@endpush

@section('content')
    <div class="main-content">
        <div class="container-fluid trn">
            <div class="lms-pagehead">
                <div>
                    <h2 class="page-title">{{ $test->name }}</h2>
                    <div class="trn-meta mt-1">Set the call, choose one scenario, and add the skills this test should score.</div>
                </div>
                <div class="lms-pagehead-actions">
                    @include('adminmodule::admin.training._badge', [
                        'text' => $test->isPublished() ? 'Published' : 'Draft',
                        'tone' => $test->isPublished() ? 'ok' : 'warn',
                    ])
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.training.calls.manage') }}">All call tests</a>
                </div>
            </div>

            @if($errors->any())
                <div class="card">
                    <div class="card-body trn-meta" style="color:#b91c1c">{{ $errors->first() }}</div>
                </div>
            @endif

            @include('adminmodule::admin.training.calls.manage._form', [
                'action' => route('admin.training.calls.manage.update', $test),
                'method' => 'PUT',
                'submitLabel' => 'Save',
            ])

            @if(! $test->isPublished())
                <form method="POST" action="{{ route('admin.training.calls.manage.publish', $test) }}">
                    @csrf
                    <div class="card">
                        <div class="card-body call-box-head">
                            <div class="trn-meta">Save the scenario and skills first. Then publish so it can be assigned.</div>
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Publish</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
